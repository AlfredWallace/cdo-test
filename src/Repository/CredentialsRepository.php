<?php

namespace App\Repository;

use App\Entity\Credentials;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Credentials>
 */
class CredentialsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Credentials::class);
    }

    public function saveCredentials(Credentials $credentials): void
    {
        $this->getEntityManager()->persist($credentials);
        $this->getEntityManager()->flush();
    }
}
