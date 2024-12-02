<?php

namespace App\CdoDemoApi;

use App\Entity\Member;
use App\Exception\CdoDemoException;
use App\Repository\MemberRepository;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class MemberFetcher
{
    private array $memoizedMembers = [];

    public function __construct(
        private readonly CdoDemoClient $cdoDemoClient,
        private readonly MemberRepository $memberRepository,
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
    public function getMemberFromCode(string $memberCode): Member
    {
        $member = $this->memberRepository->findOneBy(['code' => $memberCode]);

        // Si le membre est en base, c'est bon
        if ($member !== null) {
            return $member;
        }

        // Si dans le process php courant on n'a pas encore fait le fetch coûteux, alors il faut le faire
        if (empty($this->memoizedMembers)) {
            $this->setMembersFromApi();
        }

        // Check si le code fourni existe bien côté résultat API
        if (!array_key_exists($memberCode, $this->memoizedMembers)) {
            throw new CdoDemoException("{$memberCode} is not a valid member");
        }

        // Maintenant qu'on a les données en cache, on va insérer en base le membre
        $memberData = $this->cdoDemoClient->member($this->memoizedMembers[$memberCode]['id']);
        $member = new Member();
        $member
            ->setName($memberData['name'])
            ->setCode($memberData['code']);
        $this->memberRepository->saveMember($member);

        return $member;
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    private function setMembersFromApi(): void
    {
        $membersResponse = $this->cdoDemoClient->members();
        if (!array_key_exists('member', $membersResponse)) {
            throw new CdoDemoException("Clef 'member' introuvable dans la réponse d'API");
        }

        foreach ($membersResponse['member'] as $memberData) {
            // On va faire 2 vérifications non bloquantes

            if (!array_key_exists('code', $memberData)) {
                $this->logger->error("Pas de clef 'code' dans la réponse d'API.", ['member data' => $memberData]);
                continue;
            }

            if (!array_key_exists('id', $memberData)) {
                $this->logger->error("Pas de clef 'id' dans la réponse d'API.", ['member data' => $memberData]);
                continue;
            }

            if (!array_key_exists('name', $memberData)) {
                $this->logger->error("Pas de clef 'name' dans la réponse d'API.", ['member data' => $memberData]);
                continue;
            }

            $this->memoizedMembers[$memberData['code']] = [
                'id' => $memberData['id'],
                'name' => $memberData['name']
            ];

        }
    }
}