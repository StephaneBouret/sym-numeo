<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ProfessionalLogo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProfessionalLogo>
 */
final class ProfessionalLogoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProfessionalLogo::class);
    }
}
