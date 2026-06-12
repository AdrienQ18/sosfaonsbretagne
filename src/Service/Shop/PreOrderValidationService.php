<?php

namespace App\Service\Shop;

use App\Entity\PreOrder;
use App\Enum\PreOrderStatus;
use Doctrine\ORM\EntityManagerInterface;

final class PreOrderValidationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PreOrderMailerService $preOrderMailerService,
    ) {
    }

    public function validate(PreOrder $preOrder): void
    {
        if ($preOrder->getStatus() === PreOrderStatus::PAYEE) {
            return;
        }

        $preOrder->setStatus(PreOrderStatus::EN_ATTENTE_PAIEMENT);

        if (!$preOrder->getValidatedAt()) {
            $preOrder->setValidatedAt(new \DateTimeImmutable());
        }

        $this->entityManager->flush();
        $this->preOrderMailerService->sendPaymentLink($preOrder);
    }
}
