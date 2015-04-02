<?php

namespace AppBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * ReportRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ReportRepository extends EntityRepository
{
    /**
     * @param integer $id
     * @param integer $userId
     * @return AppBundle\Entity\Report or null
     */
    public function findByIdAndUser($id,$userId)
    {
        $qb = $this->createQueryBuilder('r');
        $qb->where('r.id = :id')->setParameter('id', $id);
        $qb->join('r.client ', 'c')->join('c.users','u')->andWhere('u.id = :user_id')->setParameter('user_id', $userId);
        $report = $qb->getQuery()->getOneOrNullResult();
        
        return $report;
    }
}
