<?php

namespace AppBundle\Entity\Repository;

use AppBundle\Entity\Client;
use AppBundle\Entity\User;
use Doctrine\ORM\EntityRepository;

/**
 * ClientRepository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ClientRepository extends EntityRepository
{
    /**
     * Search Clients
     *
     * @param string $query     Search query
     * @param string $orderBy   field to order by
     * @param string $sortOrder order of field order ASC|DESC
     * @param int    $limit     number of results to return
     * @param string $offset
     *
     * @return Client[]|array
     */
    public function searchClients($query = '', $orderBy = 'lastname', $sortOrder = 'ASC', $limit = 100, $offset = '0')
    {
        $qb = $this->createQueryBuilder('c');
        $qb->setFirstResult($offset);
        $qb->setMaxResults($limit);
        $qb->orderBy('c.' . $orderBy, $sortOrder);

        if ($query) {
            if (Client::isValidCaseNumber($query)) { // case number
                $qb->andWhere('lower(c.caseNumber) = :cn');
                $qb->setParameter('cn', strtolower($query));
            } else { // client.lastname
                $qb->andWhere('lower(c.lastname) LIKE :qLike ');
                $qb->setParameter('qLike', '%' . strtolower($query) . '%');
            }
        }

        // ensure max 100 results
        $limit = ($limit <= 100) ? $limit : 100;
        $qb->setMaxResults($limit);
        $this->_em->getFilters()->getFilter('softdeleteable')->disableForEntity(Client::class); //disable softdelete for createdBy, needed from admin area
        $clients = $qb->getQuery()->getResult(); /* @var $clients Client[] */
        $this->_em->getFilters()->enable('softdeleteable');
        return $clients;
    }

    /**
     * @param User $user
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function findAllClientIdsByUser(User $user)
    {
        $conn = $this->getEntityManager()->getConnection();
        $stmt = $conn->executeQuery(
            'select deputy_case.client_id FROM deputy_case WHERE deputy_case.user_id = ?',
            [$user->getId()]
        );

        return array_map('current', $stmt->fetchAll());
    }

    /**
     * @param User $user
     * @param $clientId
     * @throws \Doctrine\DBAL\DBALException
     */
    public function saveUserToClient(User $user, $clientId)
    {
        $conn = $this->getEntityManager()->getConnection();

        $conn->executeQuery(
            'INSERT INTO deputy_case (client_id, user_id) VALUES (?, ?) ON CONFLICT DO NOTHING',
            [$clientId, $user->getId()]
        );
    }

    /**
     * @param User $user
     * @param $teamId
     * @throws \Doctrine\DBAL\DBALException
     */
    public function saveUserToTeam(User $user, $teamId)
    {
        $conn = $this->getEntityManager()->getConnection();

        $conn->executeQuery(
            'INSERT INTO user_team (user_id, team_id) VALUES (?, ?) ON CONFLICT DO NOTHING',
            [$user->getId(), $teamId]
        );
    }

    /**
     * @param $id
     * @return null
     */
    public function getArrayById($id)
    {
        // called by ADMIN users so must include active and inactive orgs
        $query = $this
            ->getEntityManager()
            ->createQuery('SELECT c, r, ndr, o FROM AppBundle\Entity\Client c LEFT JOIN c.reports r LEFT JOIN c.ndr ndr LEFT JOIN c.organisations o WHERE c.id = ?1')
            ->setParameter(1, $id);

        $this->_em->getFilters()->getFilter('softdeleteable')->disableForEntity(Client::class);
        $result = $query->getArrayResult();
        $this->_em->getFilters()->enable('softdeleteable');

        return count($result) === 0 ? null : $result[0];
    }
}
