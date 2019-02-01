<?php

namespace AppBundle\Entity\Repository;

use AppBundle\Entity\User;
use Doctrine\ORM\EntityRepository;

/**
 * TeamRepository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class TeamRepository extends EntityRepository
{
    /**
     * @param User $user
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function findAllTeamIdsByUser(User $user)
    {
        $conn = $this->getEntityManager()->getConnection();
        $stmt = $conn->executeQuery(
            'select user_team.team_id FROM user_team WHERE user_team.user_id = ?',
            [$user->getId()]
        );

        return array_map('current', $stmt->fetchAll());
    }
}
