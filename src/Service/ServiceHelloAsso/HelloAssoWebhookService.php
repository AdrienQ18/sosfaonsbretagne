<?php

namespace App\Service\ServiceHelloAsso;

use App\Enum\DonationStatus;
use App\Repository\DonationRepository;
use App\Service\ServiceDonation\DonationMailerService;
use App\Service\ServiceDonation\DonationPdfService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

final class HelloAssoWebhookService
{
    public function __construct(
        private DonationRepository $donationRepository,
        private EntityManagerInterface $entityManager,
        private DonationPdfService $donationPdfService,
        private DonationMailerService $donationMailerService,
        private LoggerInterface $logger,
    ) {
    }

    public function handle(array $payload): array
    {
        $metadata = $payload['metadata'] ?? [];

        if (!isset($metadata['donation_id'])) {
            return [
                'status' => 400,
                'message' => 'Identifiant donation manquant.',
            ];
        }

        return $this->handleDonation($payload, (int) $metadata['donation_id']);
    }

    private function handleDonation(array $payload, int $donationId): array
    {
        $eventType = $payload['eventType'] ?? null;

        // On valide le métier sur Payment ; Order peut servir au logging/traçage.
        if ($eventType === 'Order') {
            $this->logger->info('helloasso.webhook.order_received', [
                'donation_id' => $donationId,
                'order_id' => $payload['data']['id'] ?? null,
                'checkout_intent_id' => $payload['data']['checkoutIntentId'] ?? null,
            ]);

            return [
                'status' => 200,
                'message' => 'Event Order reçu.',
            ];
        }

        if ($eventType !== 'Payment') {
            return [
                'status' => 200,
                'message' => 'Event ignoré.',
            ];
        }

        $donation = $this->donationRepository->find($donationId);
        if (!$donation) {
            return [
                'status' => 404,
                'message' => 'Donation introuvable.',
            ];
        }

        $paymentState = $payload['data']['state'] ?? null;

        $this->logger->info('helloasso.webhook.payment_received', [
            'donation_id' => $donationId,
            'state' => $paymentState,
            'payment_id' => $payload['data']['id'] ?? null,
            'order_id' => $payload['data']['order']['id'] ?? null,
        ]);

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

                // Champs recommandés à ajouter sur Donation :
                // - receiptEmailSentAt
                // - associationNotifiedAt
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
                    'message' => 'Donation validée et post-traitée.',
                ];
            } catch (\Throwable $e) {
                $this->logger->error('helloasso.webhook.payment_processing_failed', [
                    'donation_id' => $donationId,
                    'state' => $paymentState,
                    'error' => $e->getMessage(),
                ]);

                // Retour 500/503 uniquement parce que chaque étape est idempotente
                // et peut être rejouée sans dupliquer les emails.
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
                'message' => 'Paiement refusé.',
            ];
        }

        return [
            'status' => 200,
            'message' => 'Statut HelloAsso non traité.',
            'meta' => ['state' => $paymentState],
        ];
    }



private function handlePreOrder(array $payload): array
    {
        $eventType = $payload['eventType'] ?? null;
        $metadata = $payload['metadata'] ?? [];
        $preOrderId = $metadata['pre_order_id'] ?? null;

        if (!$preOrderId) {
            return [
                'status' => 400,
                'message' => 'Identifiant de précommande manquant.',
            ];
        }

        $preOrder = $this->preOrderRepository->find($preOrderId);

        if (!$preOrder) {
            return [
                'status' => 404,
                'message' => 'Précommande introuvable.',
            ];
        }

        if ($eventType !== 'Payment' && $eventType !== 'Order') {
            return [
                'status' => 200,
                'message' => 'Notification reçue, mais aucun changement nécessaire.',
            ];
        }

        $paymentState = $payload['data']['state'] ?? null;

        if ($paymentState === 'Authorized' || $paymentState === 'Paid') {
            if ($preOrder->getStatus() === PreOrderStatus::PAYEE) {
                return [
                    'status' => 200,
                    'message' => 'Précommande déjà payée.',
                ];
            }

            $preOrder->setStatus(PreOrderStatus::PAYEE);
            $preOrder->setPaidAt(new \DateTimeImmutable());

            $this->entityManager->flush();

            try {
                $pdfPath = $this->preOrderPdfService->generateInvoice($preOrder);
                $this->entityManager->flush();
                $this->preOrderMailerService->sendPaymentConfirmation($preOrder, $pdfPath);
                $this->preOrderMailerService->sendPreOrderPaymentConfirmationNotificationToAssociation($preOrder);
            } catch (\Throwable $e) {
                return [
                    'status' => 200,
                    'message' => 'Précommande payée, mais erreur lors de l’envoi des emails.',
                    'error' => $e->getMessage(),
                ];
            }

            return [
                'status' => 200,
                'message' => 'Précommande payée.',
            ];
        }

        if (
            $paymentState === 'Refused'
            || $paymentState === 'Canceled'
            || $paymentState === 'Error'
        ) {
            $preOrder->setStatus(PreOrderStatus::PAIEMENT_REFUSE);

            $this->entityManager->flush();

            return [
                'status' => 200,
                'message' => 'Paiement précommande refusé.',
            ];
        }

        return [
            'status' => 200,
            'message' => 'Notification reçue, mais le statut du paiement est inconnu.',
            'state' => $paymentState,
        ];
    }
}
