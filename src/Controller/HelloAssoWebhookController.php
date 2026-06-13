<?php

namespace App\Controller;

use App\Service\HelloAsso\HelloAssoWebhookService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur de réception des webhooks HelloAsso.
 *
 * Ce point d'entrée est appelé automatiquement par HelloAsso
 * lors de certains événements (paiement validé, don effectué,
 * précommande payée, etc.).
 *
 * Les données reçues sont ensuite transmises au service
 * HelloAssoWebhookService pour traitement métier.
 */
final class HelloAssoWebhookController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Réception et traitement d'un webhook HelloAsso.
     *
     * Sécurisation :
     * - Vérification prioritaire de la signature HMAC.
     * - Fallback sur un secret passé en query string
     *   pour assurer la compatibilité avec les anciens webhooks.
     *
     * Retourne une réponse JSON adaptée au traitement effectué.
     */
    #[Route('/helloasso/webhook', name: 'helloasso_webhook', methods: ['POST'])]
    public function __invoke(
        Request $request,
        HelloAssoWebhookService $service,
    ): JsonResponse {
        // Récupération du contenu brut envoyé par HelloAsso.
        $rawBody = $request->getContent();

        /**
         * Vérification de la signature partenaire HelloAsso.
         *
         * Cette méthode permet de garantir que la requête
         * provient bien de HelloAsso et n'a pas été modifiée.
         */
        $signatureKey = $_ENV['HELLOASSO_SIGNATURE_KEY'] ?? null;
        $receivedSignature = $request->headers->get('x-ha-signature');

        if ($signatureKey && $receivedSignature) {
            $computed = hash_hmac(
                'sha256',
                $rawBody,
                $signatureKey
            );

            if (!hash_equals($computed, $receivedSignature)) {
                $this->logger->warning(
                    'helloasso.webhook.invalid_signature'
                );

                return new JsonResponse(
                    ['error' => 'Signature invalide.'],
                    403
                );
            }
        } else {
            /**
             * Compatibilité avec l'ancien système de sécurité.
             *
             * Utilisation d'un secret passé dans l'URL :
             * /helloasso/webhook?secret=xxx
             */
            $receivedSecret = $request->query->get('secret');
            $expectedSecret = $_ENV['HELLOASSO_WEBHOOK_SECRET'] ?? null;

            if (
                !$expectedSecret ||
                !hash_equals(
                    $expectedSecret,
                    (string) $receivedSecret
                )
            ) {
                $this->logger->warning(
                    'helloasso.webhook.invalid_legacy_secret'
                );

                return new JsonResponse(
                    ['error' => 'Webhook non autorisé.'],
                    403
                );
            }
        }

        // Conversion du contenu JSON reçu en tableau PHP.
        $payload = json_decode($rawBody, true);

        // Vérification de la validité du JSON reçu.
        if (!is_array($payload)) {
            return new JsonResponse(
                ['error' => 'Payload JSON invalide.'],
                400
            );
        }

        /**
         * Transmission du payload au service métier.
         *
         * Le service se charge notamment :
         * - de mettre à jour les statuts des dons ;
         * - de mettre à jour les précommandes ;
         * - de générer les reçus fiscaux ;
         * - d'envoyer les emails associés.
         */
        $result = $service->handle($payload);

        return new JsonResponse(
            ['message' => $result['message']] + ($result['meta'] ?? []),
            $result['status']
        );
    }
}
