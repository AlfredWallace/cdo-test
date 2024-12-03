<?php

namespace App\CdoDemoApi;

use App\Exception\CdoDemoException;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

readonly class ProviderFetcher
{
    public function __construct(
        private CdoDemoClient $cdoDemoClient,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function getAvailableProvidersFromApi(): array
    {
        // /!\ la clef 'member n'a rien à voir avec l'objet 'member', c'est uniquement la structure de l'API
        $providersResponse = $this->cdoDemoClient->providers();
        if (!array_key_exists('member', $providersResponse)) {
            throw new CdoDemoException("Clef 'member' introuvable dans la réponse d'API");
        }

        $availableProviders = [];

        foreach ($providersResponse['member'] as $providerData) {
            // On va faire des vérifications non bloquantes

            if (!array_key_exists('code', $providerData)) {
                $this->logger->error("Pas de clef 'code' dans la réponse d'API.", ['provider data' => $providerData]);
                continue;
            }

            if (!array_key_exists('name', $providerData)) {
                $this->logger->error("Pas de clef 'name' dans la réponse d'API.", ['provider data' => $providerData]);
                continue;
            }

            $availableProviders[$providerData['code']] = $providerData['name'];
        }

        return $availableProviders;
    }
}