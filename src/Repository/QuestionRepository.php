<?php

namespace App\Repository;

use App\Entity\FAQ;
use App\Entity\Question;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\AST\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Question|null find($id, $lockMode = null, $lockVersion = null)
 * @method Question|null findOneBy(array $criteria, array $orderBy = null)
 * @method Question[]    findAll()
 * @method Question[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class QuestionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Question::class);
    }

    public function findWeeklyQuestions()
    {
        $date = new \DateTime('7 days ago');
        return $this->createQueryBuilder('q')
            ->andWhere('q.creationDate >= :date')
            ->setParameter('date', $date->format('y-m-d 00:00:00'))
            ->orderBy('q.creationDate', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findTodayQuestions()
    {
        $date = new \DateTime('now');
        return $this->createQueryBuilder('q')
            ->andWhere('q.creationDate BETWEEN :dateMin AND :dateMax')
            ->setParameter('dateMin', $date->format('y-m-d 00:00:00'))
            ->setParameter('dateMax', $date->format('y-m-d 23:59:59'))
            ->orderBy('q.creationDate', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }
    // /**
    //  * @return Question[] Returns an array of Question objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('q')
            ->andWhere('q.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('q.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Question
    {
        return $this->createQueryBuilder('q')
            ->andWhere('q.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
