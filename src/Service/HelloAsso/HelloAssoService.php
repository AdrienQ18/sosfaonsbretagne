<?php

namespace App\Service\HelloAsso;

use App\Entity\Donation;
use App\Entity\PreOrder;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Service de communication avec l'API HelloAsso.
 *
 * Il permet :
 * - de récupérer un token d'accès OAuth ;
 * - de créer une intention de paiement pour un don ;
 * - de créer une intention de paiement pour une précommande.
 */
final class HelloAssoService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Récupère un token d'accès auprès de l'API HelloAsso.
     *
     * Ce token est nécessaire pour appeler les routes protégées
     * de l'API HelloAsso.
     *
     * @return string Token d'accès OAuth
     */
    public function getAccessToken(): string
    {
        // Le token n'est pas mis en cache ici : chaque appel part d'un état sûr
        // et évite d'utiliser un jeton expiré lors d'une action de paiement.
        // Appel OAuth client_credentials pour obtenir le token API.
        $response = $this->httpClient->request('POST', $_ENV['HELLOASSO_AUTH_URL'], [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'body' => [
                'grant_type' => 'client_credentials',
                'client_id' => $_ENV['HELLOASSO_CLIENT_ID'],
                'client_secret' => $_ENV['HELLOASSO_CLIENT_SECRET'],
            ],
        ]);

        $data = $response->toArray();

        return $data['access_token'];
    }

    /**
     * Crée une intention de paiement HelloAsso pour un don.
     *
     * Le montant est envoyé en centimes comme attendu par HelloAsso.
     * Les métadonnées permettent ensuite d'identifier le don
     * lors du retour webhook.
     *
     * @param Donation $donation Don concerné
     *
     * @return array{
     *     redirectUrl: string,
     *     checkoutIntentId: mixed
     * }
     */
    public function createDonationCheckout(Donation $donation): array
    {
        $token = $this->getAccessToken();

        $organizationSlug = $_ENV['HELLOASSO_ORGANIZATION_SLUG'];

        // HelloAsso attend les montants en centimes.
        $amountInCents = (int) round((float) $donation->getAmount() * 100);

        // L'URL publique doit être celle accessible par HelloAsso et par le
        // navigateur du donateur, pas l'URL locale de développement.
        // URL publique du site utilisée pour construire les URLs de retour.
        $baseUrl = rtrim($_ENV['APP_PUBLIC_URL'], '/');

        /**
         * Payload envoyé à HelloAsso.
         *
         * Les URLs permettent de rediriger l'utilisateur
         * après validation, annulation ou erreur.
         */
        $payload = [
            'totalAmount' => $amountInCents,
            'initialAmount' => $amountInCents,
            'itemName' => $donation->getDonorType()?->value === 'entreprise'
                ? 'Don entreprise SOS Faons Bretagne'
                : 'Don particulier SOS Faons Bretagne',
            'backUrl' => $baseUrl . '/donation/annuler/' . $donation->getId(),
            'errorUrl' => $baseUrl . '/donation/annuler/' . $donation->getId(),
            'returnUrl' => $baseUrl . '/donation/valider/' . $donation->getId(),
            'containsDonation' => true,
            'payer' => [
                'firstName' => $donation->getFirstname(),
                'lastName' => $donation->getLastname(),
                'email' => $donation->getEmail(),
                'address' => $donation->getAddress(),
                'city' => $donation->getCity(),
                'zipCode' => $donation->getZipcode(),
                'country' => 'FRA',
            ],

            // Métadonnées utilisées pour retrouver le don dans le webhook.
            // Elles sont indispensables car le webhook ne connait pas nos routes Symfony.
            'metadata' => [
                'donation_id' => $donation->getId(),
                'donor_type' => $donation->getDonorType()?->value,
                'company_name' => $donation->getCompanyName(),
                'company_siret' => $donation->getCompanySiret(),
            ],
        ];

        // Journalisation technique sans données personnelles sensibles.
        $this->logger->info('helloasso.checkout.request', [
            'donation_id' => $donation->getId(),
            'amount_cents' => $amountInCents,
        ]);

        $response = $this->httpClient->request(
            'POST',
            $_ENV['HELLOASSO_API_URL']
            . '/organizations/'
            . $organizationSlug
            . '/checkout-intents',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]
        );

        try {
            $data = $response->toArray();
        } catch (\Throwable $e) {
            // Journalisation détaillée en cas d'erreur API HelloAsso.
            $this->logger->error('helloasso.checkout.response_error', [
                'donation_id' => $donation->getId(),
                'status_code' => $response->getStatusCode(),
                'response' => $response->getContent(false),
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException(
                'Impossible de créer le paiement HelloAsso.',
                0,
                $e
            );
        }

        $this->logger->info('helloasso.checkout.created', [
            'donation_id' => $donation->getId(),
            'checkout_intent_id' => $data['id'] ?? null,
        ]);

        return [
            'redirectUrl' => $data['redirectUrl'],
            'checkoutIntentId' => $data['id'] ?? null,
        ];
    }

    /**
     * Crée une intention de paiement HelloAsso pour une précommande.
     *
     * Les métadonnées contiennent l'identifiant de la précommande
     * afin de la retrouver lors du webhook de paiement.
     *
     * @param PreOrder $preOrder Précommande concernée
     *
     * @return array{
     *     redirectUrl: string,
     *     checkoutIntentId: mixed
     * }
     */
    public function createPreOrderCheckout(PreOrder $preOrder): array
    {
        $token = $this->getAccessToken();

        $organizationSlug = $_ENV['HELLOASSO_ORGANIZATION_SLUG'];

        // HelloAsso attend les montants en centimes.
        $amountInCents = (int) round((float) $preOrder->getTotalAmount() * 100);

        // Même principe que pour les dons : les URLs doivent rester publiques
        // pour les redirections HelloAsso.
        // URL publique du site utilisée pour construire les URLs de retour.
        $baseUrl = rtrim($_ENV['APP_PUBLIC_URL'], '/');

        $payload = [
            'totalAmount' => $amountInCents,
            'initialAmount' => $amountInCents,
            'itemName' => 'Précommande SOS Faons Bretagne',
            'backUrl' => $baseUrl . '/panier',
            'errorUrl' => $baseUrl . '/panier',
            'returnUrl' => $baseUrl . '/pre-order/paiement/valider/' . $preOrder->getId(),
            'containsDonation' => false,
            'payer' => [
                'firstName' => $preOrder->getUser()->getFirstname(),
                'lastName' => $preOrder->getUser()->getLastname(),
                'email' => $preOrder->getUser()->getEmail(),
                'address' => $preOrder->getUser()->getAddress(),
                'city' => $preOrder->getUser()->getCity(),
                'zipCode' => $preOrder->getUser()->getZipcode(),
                'country' => 'FRA',
            ],

            // Métadonnées utilisées pour retrouver la précommande dans le webhook.
            // Le type facilite le diagnostic si d'autres paiements HelloAsso sont ajoutés.
            'metadata' => [
                'pre_order_id' => $preOrder->getId(),
                'type' => 'pre_order',
            ],
        ];

        $this->logger->info('helloasso.pre_order.checkout.request', [
            'pre_order_id' => $preOrder->getId(),
            'amount_cents' => $amountInCents,
        ]);

        $response = $this->httpClient->request(
            'POST',
            $_ENV['HELLOASSO_API_URL']
            . '/organizations/'
            . $organizationSlug
            . '/checkout-intents',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]
        );

        try {
            $data = $response->toArray();
        } catch (\Throwable $e) {
            // Journalisation détaillée en cas d'erreur API HelloAsso.
            $this->logger->error('helloasso.pre_order.checkout.response_error', [
                'pre_order_id' => $preOrder->getId(),
                'status_code' => $response->getStatusCode(),
                'response' => $response->getContent(false),
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException(
                'Impossible de créer le paiement HelloAsso pour la précommande.',
                0,
                $e
            );
        }

        $this->logger->info('helloasso.pre_order.checkout.created', [
            'pre_order_id' => $preOrder->getId(),
            'checkout_intent_id' => $data['id'] ?? null,
        ]);

        return [
            'redirectUrl' => $data['redirectUrl'],
            'checkoutIntentId' => $data['id'] ?? null,
        ];
    }
}
