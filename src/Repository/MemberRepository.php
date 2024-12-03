<?php

namespace App\Repository;

use App\Entity\Member;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Member>
 */
class MemberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Member::class);
    }

    public function saveMember(Member $member, bool $flush = false): void
    {
        $this->getEntityManager()->persist($member);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function flushMembers(): void
    {
        $this->getEntityManager()->flush();
    }

    public function clearMembers(): void
    {
        $this->getEntityManager()->clear();
    }
}