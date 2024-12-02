<?php

namespace App\CdoDemoApi;

use App\Exception\CdoDemoException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CdoDemoClient
{
    private ?string $memoizedToken = null;

    public function __construct(
        private readonly HttpClientInterface $cdoDemoClient,

        #[Autowire(env: 'CDO_DEMO_USERNAME')]
        private readonly string $cdoDemoUsername,

        #[Autowire(env: 'CDO_DEMO_PASSWORD')]
        private readonly string $cdoDemoPassword,
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    private function getToken(): string
    {
        if ($this->memoizedToken !== null) {
            return $this->memoizedToken;
        }

        $content = $this->loginCheck();

        if (!array_key_exists('token', $content)) {
            throw new CdoDemoException(message: "Key 'token' not found in CDO Demo API response.");
        }

        if (empty($content['token'])) {
            throw new CdoDemoException(message: "Value at 'token' key in CDO Demo API response is empty.");
        }

        $this->memoizedToken = $content['token'];
        return $this->memoizedToken;
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    private function loginCheck(): array
    {
        $response = $this->cdoDemoClient->request(
            'POST',
            'login_check',
            [
                'json' => [
                    'username' => $this->cdoDemoUsername,
                    'password' => $this->cdoDemoPassword,
                ]
            ]
        );

        return $response->toArray();
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function providers(): array
    {
        $response = $this->cdoDemoClient->request('GET', 'providers', ['auth_bearer' => $this->getToken()]);
        return $response->toArray();
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function provider(int $id): array
    {
        $response = $this->cdoDemoClient->request('GET', 'providers/' . $id, ['auth_bearer' => $this->getToken()]);
        return $response->toArray();
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function members(): array
    {
        $response = $this->cdoDemoClient->request('GET', 'members', ['auth_bearer' => $this->getToken()]);
        return $response->toArray();
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function member(int $id): array
    {
        $response = $this->cdoDemoClient->request('GET', 'members/' . $id, ['auth_bearer' => $this->getToken()]);
        return $response->toArray();
    }
}