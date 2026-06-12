<?php

namespace App\Service\HelloAsso;

use App\Entity\Donation;
use App\Entity\PreOrder;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class HelloAssoService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private UrlGeneratorInterface $urlGenerator,
        private LoggerInterface $logger,
    ) {
    }

    public function getAccessToken(): string
    {
        $response = $this->httpClient->request('POST', $_ENV['HELLOASSO_AUTH_URL'], [
            'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
            'body' => [
                'grant_type' => 'client_credentials',
                'client_id' => $_ENV['HELLOASSO_CLIENT_ID'],
                'client_secret' => $_ENV['HELLOASSO_CLIENT_SECRET'],
            ],
        ]);

        $data = $response->toArray();
        return $data['access_token'];
    }

    public function createDonationCheckout(Donation $donation): array
    {
        $token = $this->getAccessToken();
        $organizationSlug = $_ENV['HELLOASSO_ORGANIZATION_SLUG'];
        $amountInCents = (int) round((float) $donation->getAmount() * 100);
        $baseUrl = rtrim($_ENV['APP_PUBLIC_URL'], '/');

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
            'metadata' => [
                'donation_id' => $donation->getId(),
                'donor_type' => $donation->getDonorType()?->value,
                'company_name' => $donation->getCompanyName(),
                'company_siret' => $donation->getCompanySiret(),
            ],
        ];

        $this->logger->info('helloasso.checkout.request', [
            'donation_id' => $donation->getId(),
            'amount_cents' => $amountInCents,
        ]);

        $response = $this->httpClient->request(
            'POST',
            $_ENV['HELLOASSO_API_URL'] . '/organizations/' . $organizationSlug . '/checkout-intents',
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
            $this->logger->error('helloasso.checkout.response_error', [
                'donation_id' => $donation->getId(),
                'status_code' => $response->getStatusCode(),
                'response' => $response->getContent(false),
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException('Impossible de créer le paiement HelloAsso.', 0, $e);
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


    public function createPreOrderCheckout(PreOrder $preOrder): array
    {
        $token = $this->getAccessToken();

        $organizationSlug = $_ENV['HELLOASSO_ORGANIZATION_SLUG'];
        $amountInCents = (int) round((float) $preOrder->getTotalAmount() * 100);
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
            'metadata' => [
                'pre_order_id' => $preOrder->getId(),
                'type' => 'pre_order',
            ],
        ];

        $this->logger->info('helloasso.pre_order.checkout.request', [
            'pre_order_id' => $preOrder->getId(),
            'amount_cents' => $amountInCents,
        ]);
        $this->logger->info('PREORDER METADATA', [
            'pre_order_id' => $preOrder->getId(),
        ]);
        $response = $this->httpClient->request(
            'POST',
            $_ENV['HELLOASSO_API_URL'] . '/organizations/' . $organizationSlug . '/checkout-intents',
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
