<?php

namespace App\CdoDemoApi;

use App\Entity\Credentials;
use App\Repository\CredentialsRepository;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

readonly class CdoDemoTokenFetcher
{
    private const CREDENTIALS_NAME = 'cdo_demo';

    public function __construct(
        private CdoDemoClient $cdoDemoClient,
        private CredentialsRepository $credentialsRepository,
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     */
    public function fetchToken(): string
    {
        $credentials = $this->credentialsRepository->findOneBy(['name' => self::CREDENTIALS_NAME]);

        if ($credentials === null || empty($credentials->getBearer())) {
            $token = $this->cdoDemoClient->loginCheck();

            $credentials = new Credentials();
            $credentials
                ->setName(self::CREDENTIALS_NAME)
                ->setBearer($token);

            $this->credentialsRepository->persist($credentials);
            $this->credentialsRepository->flush();
        }

        return $credentials->getBearer();
    }
}