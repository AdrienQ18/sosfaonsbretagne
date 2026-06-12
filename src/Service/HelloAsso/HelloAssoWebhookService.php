<?php

namespace App\Service\HelloAsso;

use App\Enum\DonationStatus;
use App\Enum\PreOrderStatus;
use App\Repository\DonationRepository;
use App\Repository\PreOrderRepository;
use App\Service\Donation\DonationMailerService;
use App\Service\Donation\DonationPdfService;
use App\Service\Shop\PreOrderMailerService;
use App\Service\Shop\PreOrderPdfService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service de traitement des webhooks HelloAsso.
 *
 * Ce service est appelé par le contrôleur webhook afin de :
 * - traiter les paiements de dons ;
 * - traiter les paiements de précommandes ;
 * - générer les reçus fiscaux ;
 * - générer les factures ;
 * - envoyer les emails de confirmation ;
 * - mettre à jour les statuts métier.
 */
final class HelloAssoWebhookService
{
    public function __construct(
        private DonationRepository     $donationRepository,
        private PreOrderRepository     $preOrderRepository,
        private EntityManagerInterface $entityManager,
        private DonationPdfService     $donationPdfService,
        private DonationMailerService  $donationMailerService,
        private PreOrderPdfService     $preOrderPdfService,
        private PreOrderMailerService  $preOrderMailerService,
        private LoggerInterface        $logger,
    )
    {
    }

    /**
     * Point d'entrée principal du webhook.
     *
     * Analyse les métadonnées HelloAsso afin de déterminer
     * s'il s'agit d'un don ou d'une précommande.
     *
     * @param array $payload Données reçues depuis HelloAsso
     *
     * @return array{
     *     status:int,
     *     message:string
     * }
     */
    public function handle(array $payload): array
    {
        // Les métadonnées permettent de retrouver l'entité métier concernée.
        $metadata = $payload['metadata'] ?? $payload['data']['metadata'] ?? [];

        // Routage du webhook vers le traitement approprié.
        if (isset($metadata['donation_id'])) {
            return $this->handleDonation($payload, (int)$metadata['donation_id']);
        }

        if (isset($metadata['pre_order_id'])) {
            return $this->handlePreOrder($payload, (int)$metadata['pre_order_id']);
        }

        return [
            'status' => 400,
            'message' => 'Identifiant donation ou precommande manquant.',
        ];
    }

    /**
     * Traite les événements HelloAsso liés aux dons.
     *
     * Les événements pris en charge sont :
     * - Order ;
     * - Payment.
     *
     * Lors d'un paiement validé :
     * - le don est validé ;
     * - le reçu fiscal est généré ;
     * - les emails sont envoyés.
     */
    private function handleDonation(array $payload, int $donationId): array
    {
        $eventType = $payload['eventType'] ?? null;

        // Event Order : la commande HelloAsso est créée mais le paiement n'est pas encore confirmé.
        if ($eventType === 'Order') {
            $this->logger->info('helloasso.webhook.order_received', [
                'donation_id' => $donationId,
                'order_id' => $payload['data']['id'] ?? null,
                'checkout_intent_id' => $payload['data']['checkoutIntentId'] ?? null,
            ]);

            return [
                'status' => 200,
                'message' => 'Event Order recu.',
            ];
        }
        // Seuls les événements Payment déclenchent les traitements métier.
        if ($eventType !== 'Payment') {
            return [
                'status' => 200,
                'message' => 'Event ignore.',
            ];
        }
        // Vérification de l'existence du don en base.
        $donation = $this->donationRepository->find($donationId);
        if (!$donation) {
            return [
                'status' => 404,
                'message' => 'Donation introuvable.',
            ];
        }
        // Journalisation du paiement reçu depuis HelloAsso.
        $paymentState = $payload['data']['state'] ?? null;
        $this->logger->info('helloasso.webhook.payment_received', [
            'donation_id' => $donationId,
            'state' => $paymentState,
            'payment_id' => $payload['data']['id'] ?? null,
            'order_id' => $payload['data']['order']['id'] ?? null,
        ]);
        // Paiement accepté ou capturé.
        if (in_array($paymentState, ['Authorized', 'Paid'], true)) {
            try {
                if ($donation->getStatus() !== DonationStatus::DONATION_VALIDEE) {
                    $donation->setStatus(DonationStatus::DONATION_VALIDEE);
                    $this->entityManager->flush();
                }

                $pdfPath = $donation->getReceiptPdfPath();
                $needsReceipt = !$donation->getFiscalReceiptNumber()
                    || !$pdfPath
                    || !is_file($pdfPath);

                if ($needsReceipt) {
                    $pdfPath = $this->donationPdfService->generateFiscalReceipt($donation);
                    $this->entityManager->flush();

                    $this->logger->info('helloasso.receipt.generated', [
                        'donation_id' => $donationId,
                        'path' => $pdfPath,
                    ]);
                }

                if (!$donation->getReceiptEmailSentAt()) {
                    $this->donationMailerService->sendFiscalReceipt($donation, $pdfPath);
                    $donation->setReceiptEmailSentAt(new \DateTimeImmutable());
                    $this->entityManager->flush();
                }

                if (!$donation->getAssociationNotifiedAt()) {
                    $this->donationMailerService->sendDonationNotificationToAssociation($donation);
                    $donation->setAssociationNotifiedAt(new \DateTimeImmutable());
                    $this->entityManager->flush();
                }

                return [
                    'status' => 200,
                    'message' => 'Donation validee et post-traitee.',
                ];
            } catch (\Throwable $e) {
                $this->logger->error('helloasso.webhook.payment_processing_failed', [
                    'donation_id' => $donationId,
                    'state' => $paymentState,
                    'error' => $e->getMessage(),
                ]);

                return [
                    'status' => 500,
                    'message' => 'Erreur temporaire pendant le post-traitement.',
                ];
            }
        }

        if (in_array($paymentState, ['Refused', 'Canceled', 'Error'], true)) {
            if ($donation->getStatus() !== DonationStatus::DONATION_REFUSEE) {
                $donation->setStatus(DonationStatus::DONATION_REFUSEE);
                $this->entityManager->flush();
            }

            return [
                'status' => 200,
                'message' => 'Paiement refuse.',
            ];
        }

        return [
            'status' => 200,
            'message' => 'Statut HelloAsso non traite.',
            'meta' => ['state' => $paymentState],
        ];
    }

    /**
     * Traite les événements HelloAsso liés aux précommandes.
     *
     * Lors d'un paiement validé :
     * - la précommande passe au statut PAYEE ;
     * - la facture PDF est générée ;
     * - les emails sont envoyés.
     */
    private function handlePreOrder(array $payload, int $preOrderId): array
    {
        $eventType = $payload['eventType'] ?? null;

        if ($eventType === 'Order') {
            $preOrder = $this->preOrderRepository->find($preOrderId);
            if ($preOrder && !$preOrder->getHelloassoOrderId()) {
                $preOrder->setHelloassoOrderId((string)($payload['data']['id'] ?? ''));
                $this->entityManager->flush();
            }

            $this->logger->info('helloasso.webhook.pre_order.order_received', [
                'pre_order_id' => $preOrderId,
                'order_id' => $payload['data']['id'] ?? null,
                'checkout_intent_id' => $payload['data']['checkoutIntentId'] ?? null,
            ]);

            return [
                'status' => 200,
                'message' => 'Event Order precommande recu.',
            ];
        }

        if ($eventType !== 'Payment') {
            return [
                'status' => 200,
                'message' => 'Event precommande ignore.',
            ];
        }

        $preOrder = $this->preOrderRepository->find($preOrderId);
        if (!$preOrder) {
            return [
                'status' => 404,
                'message' => 'Precommande introuvable.',
            ];
        }

        $paymentState = $payload['data']['state'] ?? null;

        $this->logger->info('helloasso.webhook.pre_order.payment_received', [
            'pre_order_id' => $preOrderId,
            'state' => $paymentState,
            'payment_id' => $payload['data']['id'] ?? null,
            'order_id' => $payload['data']['order']['id'] ?? null,
        ]);

        /**
         * Le webhook HelloAsso est la source de vérité.
         *
         * Même si l'utilisateur revient sur la page de succès,
         * le paiement n'est considéré comme valide qu'après
         * réception du webhook officiel HelloAsso.
         */
        if (in_array($paymentState, ['Authorized', 'Paid'], true)) {
            try {
                if ($preOrder->getStatus() !== PreOrderStatus::PAYEE) {
                    $preOrder->setStatus(PreOrderStatus::PAYEE);

                    if (!$preOrder->getPaidAt()) {
                        $preOrder->setPaidAt(new \DateTimeImmutable());
                    }

                    $this->entityManager->flush();
                }

                $pdfPath = $preOrder->getInvoicePdfPath();
                $needsInvoice = !$preOrder->getInvoiceReceiptNumber()
                    || !$pdfPath
                    || !is_file($pdfPath);

                if ($needsInvoice) {
                    $pdfPath = $this->preOrderPdfService->generateInvoice($preOrder);
                    $this->entityManager->flush();

                    $this->logger->info('helloasso.pre_order.invoice.generated', [
                        'pre_order_id' => $preOrderId,
                        'path' => $pdfPath,
                    ]);
                }

                if (!$preOrder->getInvoiceEmailSentAt()) {
                    $this->preOrderMailerService->sendPaymentConfirmation($preOrder, $pdfPath);
                    $preOrder->setInvoiceEmailSentAt(new \DateTimeImmutable());
                    $this->entityManager->flush();
                }

                if (!$preOrder->getAssociationNotifiedAt()) {
                    $this->preOrderMailerService->sendPreOrderPaymentConfirmationNotificationToAssociation($preOrder);
                    $preOrder->setAssociationNotifiedAt(new \DateTimeImmutable());
                    $this->entityManager->flush();
                }

                return [
                    'status' => 200,
                    'message' => 'Precommande payee et post-traitee.',
                ];
            } catch (\Throwable $e) {
                $this->logger->error('helloasso.webhook.pre_order.payment_processing_failed', [
                    'pre_order_id' => $preOrderId,
                    'state' => $paymentState,
                    'error' => $e->getMessage(),
                ]);

                return [
                    'status' => 500,
                    'message' => 'Erreur temporaire pendant le post-traitement de la precommande.',
                ];
            }
        }

        if (in_array($paymentState, ['Refused', 'Canceled', 'Error'], true)) {
            if ($preOrder->getStatus() !== PreOrderStatus::PAIEMENT_REFUSE) {
                $preOrder->setStatus(PreOrderStatus::PAIEMENT_REFUSE);
                $this->entityManager->flush();
            }

            return [
                'status' => 200,
                'message' => 'Paiement precommande refuse.',
            ];
        }

        return [
            'status' => 200,
            'message' => 'Statut HelloAsso precommande non traite.',
            'meta' => ['state' => $paymentState],
        ];
    }
}
