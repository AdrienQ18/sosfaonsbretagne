<?php

namespace App\Service;

use App\Enum\DonationStatus;
use App\Repository\DonationRepository;
use Doctrine\ORM\EntityManagerInterface;

class HelloAssoWebhookService
{
    public function __construct(
        private DonationRepository     $donationRepository,
        private EntityManagerInterface $entityManager,
        private DonationPdfService     $donationPdfService,
        private DonationMailerService  $donationMailerService,
    )
    {
    }

    /**
     * Traite une notification webhook envoyée par HelloAsso.
     *
     * Cette méthode :
     * - récupère l'identifiant de donation envoyé dans les metadata
     * - vérifie l'existence de la donation
     * - met à jour son statut selon l'état du paiement
     * - génère le reçu fiscal uniquement si le paiement est validé
     */
    public function handle(array $payload): array
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
            $donation->setStatus(DonationStatus::DONATION_VALIDEE);

            $pdfPath = $this->donationPdfService->generateFiscalReceipt($donation);

            $this->donationMailerService->sendFiscalReceipt($donation, $pdfPath);

        } elseif (
            $paymentState === 'Refused'
            || $paymentState === 'Canceled'
            || $paymentState === 'Error'
        ) {
            $donation->setStatus(DonationStatus::DONATION_REFUSEE);
        } else {
            return [
                'status' => 200,
                'message' => 'Notification reçue, mais le statut du paiement est inconnu.',
                'state' => $paymentState,
            ];
        }

        $this->entityManager->flush();

        return [
            'status' => 200,
            'message' => 'Statut de la donation mis à jour.',
        ];
    }
}
