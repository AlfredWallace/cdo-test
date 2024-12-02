<?php

namespace App\CdoDemoApi;

use App\Entity\Provider;
use App\Exception\CdoDemoException;
use App\Repository\ProviderRepository;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class ProviderFetcher
{
    private array $memoizedProviders = [];

    public function __construct(
        private readonly CdoDemoClient $cdoDemoClient,
        private readonly ProviderRepository $providerRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function getProviderFromCode(string $providerCode): Provider
    {
        $provider = $this->providerRepository->findOneBy(['code' => $providerCode]);

        // Si le provider est en base, c'est bon
        if ($provider !== null) {
            return $provider;
        }

        // Si dans le process php courant on n'a pas encore fait le fetch coûteux, alors il faut le faire
        if (empty($this->memoizedProviders)) {
            $this->setProvidersFromApi();
        }

        // Check si le code fourni existe bien côté résultat API
        if (!array_key_exists($providerCode, $this->memoizedProviders)) {
            throw new CdoDemoException("{$providerCode} is not a valid provider");
        }

        // Maintenant qu'on a les données en cache, on va insérer en base le provider
        $providerData = $this->cdoDemoClient->provider($this->memoizedProviders[$providerCode]['id']);
        $provider = new Provider();
        $provider
            ->setName($providerData['name'])
            ->setCode($providerData['code']);
        $this->providerRepository->saveProvider($provider);

        return $provider;
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    private function setProvidersFromApi(): void
    {
        $providersResponse = $this->cdoDemoClient->providers();
        if (!array_key_exists('member', $providersResponse)) {
            throw new CdoDemoException("Clef 'member' introuvable dans la réponse d'API");
        }

        foreach ($providersResponse['member'] as $providerData) {
            // On va faire 2 vérifications non bloquantes

            if (!array_key_exists('code', $providerData)) {
                $this->logger->error("Pas de clef 'code' dans la réponse d'API.", ['provider data' => $providerData]);
                continue;
            }

            if (!array_key_exists('id', $providerData)) {
                $this->logger->error("Pas de clef 'id' dans la réponse d'API.", ['provider data' => $providerData]);
                continue;
            }

            if (!array_key_exists('name', $providerData)) {
                $this->logger->error("Pas de clef 'name' dans la réponse d'API.", ['provider data' => $providerData]);
                continue;
            }

            $this->memoizedProviders[$providerData['code']] = [
                'id' => $providerData['id'],
                'name' => $providerData['name']
            ];
        }
    }
}