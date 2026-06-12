<?php

namespace App\Service\ServiceHelloAsso;

use App\Enum\DonationStatus;
use App\Repository\DonationRepository;
use App\Repository\PreOrderRepository;
use App\Service\ServiceDonation\DonationMailerService;
use App\Service\ServiceDonation\DonationPdfService;
use App\Service\ServiceShop\PreOrderMailerService;
use App\Service\ServiceShop\PreOrderPdfService;
use Doctrine\ORM\EntityManagerInterface;
use App\Enum\PreOrderStatus;

class HelloAssoWebhookService
{
    public function __construct(
        private DonationRepository     $donationRepository,
        private EntityManagerInterface $entityManager,
        private DonationPdfService     $donationPdfService,
        private DonationMailerService  $donationMailerService,
        private PreOrderRepository     $preOrderRepository,
        private PreOrderPdfService     $preOrderPdfService,
        private PreOrderMailerService  $preOrderMailerService,
    )
    {
    }

    public function handle(array $payload): array
    {
        $metadata = $payload['metadata'] ?? [];

        if (isset($metadata['donation_id'])) {
            return $this->handleDonation($payload);
        }

        if (isset($metadata['pre_order_id'])) {
            return $this->handlePreOrder($payload);
        }

        return [
            'status' => 400,
            'message' => 'Aucun identifiant donation ou précommande trouvé.',
        ];
    }

    public function handleDonation(array $payload): array
    {
        $eventType = $payload['eventType'] ?? null;
        $metadata = $payload['metadata'] ?? [];
        $donationId = $metadata['donation_id'] ?? null;

        if (!$donationId) {
            return [
                'status' => 400,
                'message' => 'Identifiant de donation manquant.',
            ];
        }

        $donation = $this->donationRepository->find($donationId);

        if (!$donation) {
            return [
                'status' => 404,
                'message' => 'Aucune donation ne correspond à cet identifiant.',
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

            if ($donation->getStatus() === DonationStatus::DONATION_VALIDEE) {
                return [
                    'status' => 200,
                    'message' => 'Donation déjà validée.',
                ];
            }
            $donation->setStatus(DonationStatus::DONATION_VALIDEE);
            $this->entityManager->flush();

            try {
                $pdfPath = $this->donationPdfService->generateFiscalReceipt($donation);
                $this->entityManager->flush();
                $this->donationMailerService->sendFiscalReceipt($donation, $pdfPath);
                $this->donationMailerService->sendDonationNotificationToAssociation($donation);
            } catch (\Throwable $e) {
                return [
                    'status' => 200,
                    'message' => 'Donation validée, mais erreur lors de l’envoi des emails.',
                    'error' => $e->getMessage(),
                ];
            }

        } elseif (
            $paymentState === 'Refused'
            || $paymentState === 'Canceled'
            || $paymentState === 'Error'
        ) {
            $donation->setStatus(DonationStatus::DONATION_REFUSEE);
            $this->entityManager->flush();
        } else {
            return [
                'status' => 200,
                'message' => 'Notification reçue, mais le statut du paiement est inconnu.',
                'state' => $paymentState,
            ];
        }

        return [
            'status' => 200,
            'message' => 'Statut de la donation mis à jour.',
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
