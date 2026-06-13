<?php

namespace App\Service\Shop;

use App\Entity\PreOrder;
use App\Enum\PreOrderStatus;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service de validation des précommandes.
 *
 * Ce service est utilisé lorsqu'un administrateur
 * valide une précommande.
 *
 * La validation :
 * - met à jour le statut ;
 * - enregistre la date de validation ;
 * - envoie le lien de paiement au client.
 */
final class PreOrderManagementService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PreOrderMailerService $preOrderMailerService,
    ) {
    }

    /**
     * Valide une précommande.
     *
     * Une précommande déjà payée ne peut plus être validée.
     *
     * @param PreOrder $preOrder Précommande à valider
     */
    public function validate(PreOrder $preOrder): void
    {
        // Une précommande déjà payée ne doit pas être retraitée.
        // Cela évite notamment de renvoyer un lien de paiement après paiement.
        if ($preOrder->getStatus() === PreOrderStatus::PAYEE) {
            return;
        }

        /**
         * Passage au statut :
         * "En attente de paiement".
         *
         * Le client recevra ensuite un lien HelloAsso
         * lui permettant de régler sa commande.
         */
        $preOrder->setStatus(
            PreOrderStatus::EN_ATTENTE_PAIEMENT
        );

        // Enregistrement de la date de validation uniquement la première fois.
        if (!$preOrder->getValidatedAt()) {
            $preOrder->setValidatedAt(
                new \DateTimeImmutable()
            );
        }

        // Sauvegarde des modifications en base.
        $this->entityManager->flush();

        // Le lien de paiement est généré dans le template à partir de la route
        // de paiement de la précommande validée.
        // Envoi du lien de paiement au client.
        $this->preOrderMailerService->sendPaymentLink($preOrder);
    }

    public function refuse(PreOrder $preOrder): void
    {
        if ($preOrder->getStatus() === PreOrderStatus::PAYEE) {
            return;
        }

        $preOrder->setStatus(PreOrderStatus::REFUSEE);

        $this->entityManager->flush();

        $this->preOrderMailerService->sendRefusedNotification($preOrder);
    }
}
