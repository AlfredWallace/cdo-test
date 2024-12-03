<?php

namespace App\CdoDemoApi;

use App\Exception\CdoDemoException;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

readonly class MemberFetcher
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
    public function getAvailableMembersFromApi(): array
    {
        // /!\ la clef 'member n'a rien à voir avec l'objet 'member', c'est uniquement la structure de l'API
        $membersResponse = $this->cdoDemoClient->members();
        if (!array_key_exists('member', $membersResponse)) {
            throw new CdoDemoException("Clef 'member' introuvable dans la réponse d'API");
        }

        $availableMembers = [];

        foreach ($membersResponse['member'] as $memberData) {
            // On va faire des vérifications non bloquantes

            if (!array_key_exists('code', $memberData)) {
                $this->logger->error("Pas de clef 'code' dans la réponse d'API.", ['member data' => $memberData]);
                continue;
            }

            if (!array_key_exists('name', $memberData)) {
                $this->logger->error("Pas de clef 'name' dans la réponse d'API.", ['member data' => $memberData]);
                continue;
            }

            if (!array_key_exists('status', $memberData)) {
                $this->logger->error("Pas de clef 'status' dans la réponse d'API.", ['member data' => $memberData]);
                continue;
            }

            // Pas une erreur, mais on ne doit prendre que les membres actifs
            if ($memberData['status'] !== 'A') {
                continue;
            }

            $availableMembers[$memberData['code']] = $memberData['name'];
        }

        return $availableMembers;
    }
}