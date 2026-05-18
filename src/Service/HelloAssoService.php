<?php

namespace App\Service;

use App\Entity\Donation;
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
                    'backUrl' => $_ENV['APP_PUBLIC_URL'] . '/donation/cancel/' . $donation->getId(),
                    'errorUrl' => $_ENV['APP_PUBLIC_URL'] . '/donation/cancel/' . $donation->getId(),
                    'returnUrl' => $_ENV['APP_PUBLIC_URL'] . '/donation/success/' . $donation->getId(),
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
}
