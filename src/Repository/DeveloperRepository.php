<?php

namespace App\Repository;

use App\Entity\Developer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Developer|null find($id, $lockMode = null, $lockVersion = null)
 * @method Developer|null findOneBy(array $criteria, array $orderBy = null)
 * @method Developer[]    findAll()
 * @method Developer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DeveloperRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Developer::class);
    }

    /**
     * Get all developers
     *
     * @return array
     */
    public function getAllDeveloperWithSkill()
    {
        return $this->createQueryBuilder('d')
            ->select('d.id', '(d.difficulty * d.period) as skill')
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * Get all developers ids and names
     *
     * @return array
     */
    public function getAllDevelopersIdAndName()
    {
        return $this->createQueryBuilder('d')
            ->select('d.id', 'd.name')
            ->getQuery()
            ->getArrayResult();
    }
}
