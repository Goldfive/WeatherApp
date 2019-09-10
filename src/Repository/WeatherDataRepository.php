<?php

namespace App\Repository;

use App\Entity\WeatherData;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method WeatherData|null find($id, $lockMode = null, $lockVersion = null)
 * @method WeatherData|null findOneBy(array $criteria, array $orderBy = null)
 * @method WeatherData[]    findAll()
 * @method WeatherData[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WeatherDataRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WeatherData::class);
    }

    public function findByDateAndLocation($searchArray) {

        if (array_key_exists('date', $searchArray)) {
            $qb = $this->createQueryBuilder('w')
                ->where("w.date = :date")
                ->andWhere('w.location = :location')
                ->setParameter('date', $searchArray['date'])
                ->setParameter('location', $searchArray['location'])
                ->getQuery()
                ->getResult();
            return $qb;
        }
        return [];
    }
}
