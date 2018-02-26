<?php

namespace AppBundle\Controller\Report;

use AppBundle\Controller\RestController;
use AppBundle\Entity as EntityDir;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;

class ProfServiceFeeController extends RestController
{
    /**
     * @Route("/report/{reportId}/prof-service-fee")
     * @Method({"POST"})
     * @Security("has_role('ROLE_DEPUTY')")
     */
    public function addAction(Request $request, $reportId)
    {
        $data = $this->deserializeBodyContent($request);

        $report = $this->findEntityBy(EntityDir\Report\Report::class, $reportId);
        $this->denyAccessIfReportDoesNotBelongToUser($report);
        $profServiceFee = new EntityDir\Report\ProfServiceFee($report);
        $profServiceFee->setReport($report);

        $this->updateEntity($data, $profServiceFee);

        $profServiceFee->setFeeTypeId('current');
        $profServiceFee->setAmountCharged(0);

        $this->persistAndFlush($profServiceFee);

        return ['id' => $profServiceFee->getId()];
    }

    /**
     * @Route("/prof-service-fee/{id}")
     * @Method({"PUT"})
     * @Security("has_role('ROLE_PROF')")
     */
    public function updateAction(Request $request, $id)
    {
        $profServiceFee = $this->findEntityBy(EntityDir\Report\ProfServiceFee::class, $id);
        $this->denyAccessIfReportDoesNotBelongToUser($profServiceFee->getReport());

        $data = $this->deserializeBodyContent($request);
        $this->updateEntity($data, $profServiceFee);

        $this->getEntityManager()->flush($profServiceFee);

        return ['id' => $profServiceFee->getId()];
    }

    /**
     * @Route("/{reportId}/prof-service-fee")
     * @Method({"GET"})
     * @Security("has_role('ROLE_PROF')")
     *
     * @param int $reportId
     */
//    public function findByReportIdAction($reportId)
//    {
//        $report = $this->findEntityBy(EntityDir\Report\Report::class, $reportId);
//        $this->denyAccessIfReportDoesNotBelongToUser($report);
//
//        $ret = $this->getRepository(EntityDir\Report\ProfServiceFee::class)->findByReport($report);
//
//        return $ret;
//    }

    /**
     * @Route("/prof-service-fee/{id}")
     * @Method({"GET"})
     * @Security("has_role('ROLE_PROF')")

     * @param Request $request
     * @param $id
     * @return null|object
     */
    public function getOneById(Request $request, $id)
    {
        $serialiseGroups = $request->query->has('groups')
            ? (array) $request->query->get('groups') : ['prof_service_fee'];
        $this->setJmsSerialiserGroups($serialiseGroups);

        $profServiceFee = $this->findEntityBy(EntityDir\Report\ProfServiceFee::class, $id, 'Prof Service Fee with id:' . $id . ' not found');
        $this->denyAccessIfReportDoesNotBelongToUser($profServiceFee->getReport());

        return $profServiceFee;
    }

    /**
     * @Route("/prof-service-fee/{id}")
     * @Method({"DELETE"})
     * @Security("has_role('ROLE_PROF')")
     */
    public function deleteProfServiceFee($id)
    {
        $profServiceFee = $this->findEntityBy(EntityDir\Report\ProfServiceFee::class, $id, 'Prof Service fee not found');
        $this->denyAccessIfReportDoesNotBelongToUser($profServiceFee->getReport());

        $this->getEntityManager()->remove($profServiceFee);
        $this->getEntityManager()->flush($profServiceFee);

        return [];
    }

    /**
     * @param array                      $data
     * @param EntityDir\Report\ProfServiceFee $profServiceFee
     *
     * @return \AppBundle\Entity\Report\Report $report
     */
    private function updateEntity(array $data, EntityDir\Report\ProfServiceFee $profServiceFee)
    {
        if (array_key_exists('assessed_or_fixed', $data)) {
            $profServiceFee->setAssessedOrFixed($data['assessed_or_fixed']);
        }

        if (array_key_exists('fee_type_id', $data)) {
            $profServiceFee->setFeeTypeId($data['fee_type_id']);
        }

        if (array_key_exists('service_type_id', $data)) {
            $profServiceFee->setServiceTypeId($data['service_type_id']);
        }

        if (array_key_exists('payment_received', $data)) {
            $profServiceFee->setPaymentReceived($data['payment_received']);
        }

        if (array_key_exists('amount_received', $data)) {
            $profServiceFee->setAmountCharged($data['amount_received']);
        }

        if (array_key_exists('payment_received_date', $data)) {
            $profServiceFee->setPaymentReceivedDate($data['paymentR_received_date']);
        }

        return $profServiceFee;
    }
}
