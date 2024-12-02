<?php

namespace App\CdoDemoApi;

use App\Exception\CdoDemoException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpClient\HttpOptions;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CdoDemoClient
{
    public function __construct(
        private HttpClientInterface $httpClient,

        #[Autowire(env: 'CDO_DEMO_USERNAME')]
        private readonly string $cdoDemoUsername,

        #[Autowire(env: 'CDO_DEMO_PASSWORD')]
        private readonly string $cdoDemoPassword,
    ) {
        $this->httpClient = $httpClient->withOptions(
            (new HttpOptions())
                ->setBaseUri("https://api-demo.cdo.fr/api/")
                ->toArray()
        );
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     */
    public function getToken(): string
    {
        $response = $this->httpClient->request(
            'POST',
            'login_check',
            [
                'json' => [
                    'username' => $this->cdoDemoUsername,
                    'password' => $this->cdoDemoPassword,
                ]
            ]
        );

        $content = $response->toArray();

        if (!array_key_exists('token', $content)) {
            throw new CdoDemoException(message: "Key 'token' not found in CDO Demo API response.");
        }

        return $content['token'];
    }
}