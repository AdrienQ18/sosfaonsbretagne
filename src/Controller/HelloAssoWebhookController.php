<?php

namespace App\Controller;

use App\Service\ServiceHelloAsso\HelloAssoWebhookService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class HelloAssoWebhookController extends AbstractController
{
    #[Route('/helloasso/webhook', name: 'helloasso_webhook', methods: ['POST'])]
    public function __invoke(
        Request $request,
        HelloAssoWebhookService $service,
        LoggerInterface $logger,
    ): JsonResponse {
        $rawBody = $request->getContent();

        // Authentification signature partenaire
        $signatureKey = $_ENV['HELLOASSO_SIGNATURE_KEY'] ?? null;
        $receivedSignature = $request->headers->get('x-ha-signature');

        if ($signatureKey && $receivedSignature) {
            $computed = hash_hmac('sha256', $rawBody, $signatureKey);

            if (!hash_equals($computed, $receivedSignature)) {
                $logger->warning('helloasso.webhook.invalid_signature');
                return new JsonResponse(['error' => 'Signature invalide.'], 403);
            }
        } else {
            // Fallback legacy : secret de query string
            $receivedSecret = $request->query->get('secret');
            $expectedSecret = $_ENV['HELLOASSO_WEBHOOK_SECRET'] ?? null;

            if (!$expectedSecret || !hash_equals($expectedSecret, (string) $receivedSecret)) {
                $logger->warning('helloasso.webhook.invalid_legacy_secret');
                return new JsonResponse(['error' => 'Webhook non autorisé.'], 403);
            }
        }

        $payload = json_decode($rawBody, true);
        if (!is_array($payload)) {
            return new JsonResponse(['error' => 'Payload JSON invalide.'], 400);
        }

        $result = $service->handle($payload);

        return new JsonResponse(
            ['message' => $result['message']] + ($result['meta'] ?? []),
            $result['status']
        );
    }
}
