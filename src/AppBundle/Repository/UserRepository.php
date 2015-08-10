<?php
namespace AppBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * UserRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class UserRepository extends EntityRepository
{
    /**
     * @param string $email
     * @return \AppBundle\Entity\User | null
     */
    public function getByEmail($email)
    {
        $qb = $this->createQueryBuilder('u');
        $qb->where('u.email = :email')->setParameter('email', $email);
        $qb->join('u.role','r')->andWhere('r.role != :role')->setParameter('role', 'ROLE_ADMIN');
        
        return $qb->getQuery()->getOneOrNullResult();
    }
    
    /**
     * @param string $email
     * @return \AppBundle\Entity\User | null
     */
    public function getAdminByEmail($email)
    {
        $qb = $this->createQueryBuilder('u');
        $qb->where('u.email = :email')->setParameter('email', $email);
        $qb->join('u.role','r')->andWhere('r.role = :role')->setParameter('role', 'ROLE_ADMIN');
        
        return $qb->getQuery()->getOneOrNullResult();
    }

    public function getAllUsers($orderBy, $sortOrder)
    {
       $qb = $this->createQueryBuilder('u');
       $qb->orderBy('u.'.$orderBy, $sortOrder);

       return $qb->getQuery()->execute();
    }
}
