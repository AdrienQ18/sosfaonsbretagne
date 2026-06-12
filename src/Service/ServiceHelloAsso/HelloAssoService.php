<?php

namespace App\Service\ServiceHelloAsso;

use App\Entity\Donation;
use App\Entity\PreOrder;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class HelloAssoService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
    ) {
    }

    public function getAccessToken(): string
    {
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

    public function createDonationCheckout(Donation $donation): string
    {
        $token = $this->getAccessToken();

        $organizationSlug = $_ENV['HELLOASSO_ORGANIZATION_SLUG'];
        $amountInCents = (int) ($donation->getAmount() * 100);

        $response = $this->httpClient->request(
            'POST',
            $_ENV['HELLOASSO_API_URL'] . '/organizations/' . $organizationSlug . '/checkout-intents',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'totalAmount' => $amountInCents,
                    'initialAmount' => $amountInCents,
                    'itemName' => $donation->getDonorType()?->value === 'entreprise'
                        ? 'Don entreprise SOS Faons Bretagne'
                        : 'Don particulier SOS Faons Bretagne',
                    'backUrl' => $_ENV['APP_PUBLIC_URL'] . '/donation/annuler/' . $donation->getId(),
                    'errorUrl' => $_ENV['APP_PUBLIC_URL'] . '/donation/annuler/' . $donation->getId(),
                    'returnUrl' => $_ENV['APP_PUBLIC_URL'] . '/donation/valider/' . $donation->getId(),
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
                ],
            ]
        );

        try {
            $data = $response->toArray();
        } catch (\Throwable $e) {
            $this->logger->error('Erreur lors de la création du checkout HelloAsso.', [
                'status_code' => $response->getStatusCode(),
                'response' => $response->getContent(false),
                'exception' => $e->getMessage(),
            ]);

            throw new \RuntimeException('Impossible de créer le paiement HelloAsso.');
        }

        return $data['redirectUrl'];
    }
    public function createPreOrderCheckout(PreOrder $preOrder): string
    {
        $token = $this->getAccessToken();

        $organizationSlug = $_ENV['HELLOASSO_ORGANIZATION_SLUG'];
        $amountInCents = (int) ((float) $preOrder->getTotalAmount() * 100);

        $response = $this->httpClient->request(
            'POST',
            $_ENV['HELLOASSO_API_URL'] . '/organizations/' . $organizationSlug . '/checkout-intents',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'totalAmount' => $amountInCents,
                    'initialAmount' => $amountInCents,
                    'itemName' => 'Précommande SOS Faons Bretagne',
                    'backUrl' => $_ENV['APP_PUBLIC_URL'] . '/cart',
                    'errorUrl' => $_ENV['APP_PUBLIC_URL'] . '/cart',
                    'returnUrl' => $_ENV['APP_PUBLIC_URL'] . '/pre-order/paiement/valider/' . $preOrder->getId(),
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
                ],
            ]
        );

        try {
            $data = $response->toArray();
        } catch (\Throwable $e) {
            $this->logger->error('Erreur lors de la création du checkout HelloAsso pour une précommande.', [
                'pre_order_id' => $preOrder->getId(),
                'status_code' => $response->getStatusCode(),
                'response' => $response->getContent(false),
                'exception' => $e->getMessage(),
            ]);

            throw new \RuntimeException('Impossible de créer le paiement HelloAsso pour la précommande.');
        }

        return $data['redirectUrl'];
    }
}
