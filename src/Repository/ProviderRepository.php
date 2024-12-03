<?php

namespace App\Repository;

use App\Entity\Provider;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Provider>
 */
class ProviderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Provider::class);
    }

    public function saveProvider(Provider $provider, bool $flush = false): void
    {
        $this->getEntityManager()->persist($provider);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function flushProviders(): void
    {
        $this->getEntityManager()->flush();
    }

    public function clearProviders(): void
    {
        $this->getEntityManager()->clear();
    }
}