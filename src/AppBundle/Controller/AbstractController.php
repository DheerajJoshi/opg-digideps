<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use AppBundle\Entity\Client;

class AbstractController extends Controller
{
    /**
     * @return array $choices
     */
    public function getAllowedCourtOrderTypeChoiceOptions(array $filter = [], $sort = null)
    {
        $responseArray = $this->get('restClient')->get('court-order-type', 'array');

        if (!empty($filter)) {
            foreach ($responseArray['court_order_types'] as $value) {
                if (in_array($value['id'], $filter)) {
                    $choices[$value['id']] = $value['name'];
                }
            }
        } else {
            foreach ($responseArray['court_order_types'] as $value) {
                $choices[$value['id']] = $value['name'];
            }
        }

        if ($sort != null) {
            $sort($choices);
        }
        return $choices;
    }


    /**
     * @param integer $clientId
     * @param integer $userId for secutity check (if present)
     * @param array $groups
     * 
     * @return Client
     */
    public function getClient($clientId, array $groups = [ "basic"])
    {
        return $this->get('restClient')->get('client/' . $clientId, 'Client', [ 'query' => [ 'groups' => $groups]]);
    }


    /**
     * @param integer $reportId
     * @param integer $userId for secutity checks (if present)
     * @param array $groups
     * 
     * @return Report
     */
    public function getReport($reportId, array $groups/* = [ 'transactions', 'basic']*/)
    {
        return $this->get('restClient')->get("/report/{$reportId}", 'Report', [ 'query' => [ 'groups' => $groups]]);
    }


    /**
     * @param Client $client
     * 
     * @return Report[]
     */
    public function getReportsIndexedById(Client $client, $groups)
    {
        $reportIds = $client->getReports();

        if (empty($reportIds)) {
            return [];
        }

        $ret = [];
        foreach ($reportIds as $id) {
            $ret[$id] = $this->getReport($id,$groups);
        }

        return $ret;
    }
}
