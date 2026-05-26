<?php

namespace App\Service\ServiceShop;

use App\Entity\PreOrder;
use App\Enum\PreOrderStatus;
use App\Service\ServiceHelloAsso\HelloAssoService;
use Doctrine\ORM\EntityManagerInterface;

class PreOrderValidationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private HelloAssoService       $helloAssoService,
        private PreOrderMailerService  $preOrderMailerService,
    )
    {
    }

    public function validate(PreOrder $preOrder): void
    {
        $paymentUrl = $this->helloAssoService->createPreOrderCheckout($preOrder);

        $preOrder->setStatus(PreOrderStatus::EN_ATTENTE_PAIEMENT);
        $preOrder->setValidatedAt(new \DateTimeImmutable());

        $this->entityManager->flush();

        $this->preOrderMailerService->sendPaymentLink($preOrder);
    }
}
