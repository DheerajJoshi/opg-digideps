<?php

namespace AppBundle\Controller\Report;

use AppBundle\Controller\RestController;
use AppBundle\Entity as EntityDir;
use AppBundle\Entity\Report\Report;
use AppBundle\Entity\Repository\ReportRepository;
use AppBundle\Service\ReportService;
use AppBundle\Service\RestHandler\Report\DeputyCostsEstimateReportUpdateHandler;
use Doctrine\ORM\AbstractQuery;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/report")
 */
class ReportController extends RestController
{
    /** @var array */
    private $updateHandlers;

    /** @var EntityDir\Repository\ReportRepository */
    private $repository;

    /** @var ReportService */
    private $reportService;

    /**
     * @param array $updateHandlers
     * @param EntityDir\Repository\ReportRepository $repository
     * @param ReportService $reportService
     */
    public function __construct(array $updateHandlers, EntityDir\Repository\ReportRepository $repository, ReportService $reportService)
    {
        $this->updateHandlers = $updateHandlers;
        $this->repository = $repository;
        $this->reportService = $reportService;
    }

    /**
     * Add a report
     * Currently only used by Lay deputy during registration steps
     * Pa report are instead created via OrgService::createReport()
     *
     * @Route("", methods={"POST"})
     * @Security("has_role('ROLE_DEPUTY')")
     */
    public function addAction(Request $request)
    {
        $reportData = $this->deserializeBodyContent($request);

        if (empty($reportData['client']['id'])) {
            throw new \InvalidArgumentException('Missing client.id');
        }
        $client = $this->findEntityBy(EntityDir\Client::class, $reportData['client']['id']);
        $this->denyAccessIfClientDoesNotBelongToUser($client);

        $this->validateArray($reportData, [
            'start_date' => 'notEmpty',
            'end_date' => 'notEmpty',
        ]);

        // report type is taken from CASREC. In case that's not available (shouldn't happen unless casrec table is dropped), use a 102
        $reportType = $this->get('opg_digideps.report_service')->getReportTypeBasedOnCasrec($client) ?: Report::TYPE_102;
        $report = new Report($client, $reportType, new \DateTime($reportData['start_date']), new \DateTime($reportData['end_date']));
        $report->setReportSeen(true);

        $report->updateSectionsStatusCache($report->getAvailableSections());
        $this->persistAndFlush($report);

        return ['report' => $report->getId()];
    }

    /**
     * @Route("/{id}", requirements={"id":"\d+"}, methods={"GET"})
     * @Security("has_role('ROLE_DEPUTY') or has_role('ROLE_CASE_MANAGER')")
     *
     * @param int $id
     *
     * @return Report
     */
    public function getById(Request $request, $id)
    {
        $groups = $request->query->has('groups')
            ? (array)$request->query->get('groups') : ['report'];

        $this->setJmsSerialiserGroups($groups);

        /* @var $report Report */
        if ($this->isGranted(EntityDir\User::ROLE_CASE_MANAGER)) {
            $this->getEntityManager()->getFilters()->getFilter('softdeleteable')->disableForEntity(EntityDir\Client::class);
            $report = $this->findEntityBy(EntityDir\Report\Report::class, $id);
            $this->getEntityManager()->getFilters()->enable('softdeleteable');
        } else {
            $report = $this->findEntityBy(EntityDir\Report\Report::class, $id);
            $this->denyAccessIfReportDoesNotBelongToUser($report);
        }

        return $report;
    }

    /**
     * @Route("/{id}/submit", requirements={"id":"\d+"}, methods={"PUT"})
     * @Security("has_role('ROLE_DEPUTY')")
     */
    public function submit(Request $request, $id)
    {
        $currentReport = $this->findEntityBy(EntityDir\Report\Report::class, $id, 'Report not found');
        /* @var $currentReport Report */
        $this->denyAccessIfReportDoesNotBelongToUser($currentReport);

        $data = $this->deserializeBodyContent($request);

        if (empty($data['submit_date'])) {
            throw new \InvalidArgumentException('Missing submit_date');
        }

        if (empty($data['agreed_behalf_deputy'])) {
            throw new \InvalidArgumentException('Missing agreed_behalf_deputy');
        }

        if ($data['agreed_behalf_deputy'] === 'not_deputy' && $this->getUser()->isLayDeputy()) {
            throw new \InvalidArgumentException('\'not_deputy\' is invalid option of agreed_behalf_deputy for lay deputies');
        }

        $currentReport->setAgreedBehalfDeputy($data['agreed_behalf_deputy']);
        $xplanation = ($data['agreed_behalf_deputy'] === 'more_deputies_not_behalf')
            ? $data['agreed_behalf_deputy_explanation'] : null;
        $currentReport->setAgreedBehalfDeputyExplanation($xplanation);

        // submit and create new year's report
        $nextYearReport = $this->get('opg_digideps.report_service')
            ->submit($currentReport, $this->getUser(), new \DateTime($data['submit_date']));

        //response to pass back. if the report was alreay submitted, no NY report is created
        return $nextYearReport ? $nextYearReport->getId() : null;
    }

    /**
     * @Route("/{id}", requirements={"id":"\d+"}, methods={"PUT"})
     * @Security("has_role('ROLE_DEPUTY')")
     */
    public function update(Request $request, $id)
    {
        /* @var $report Report */
        $report = $this->findEntityBy(EntityDir\Report\Report::class, $id, 'Report not found');


        // deputies can only edit their own reports
        if (!$this->isGranted(EntityDir\User::ROLE_ADMIN)) {
            $this->denyAccessIfReportDoesNotBelongToUser($report);
        }

        $data = $this->deserializeBodyContent($request);

        if (!empty($data['type'])) {
            $report->setType($data['type']);
        }

        if (array_key_exists('has_debts', $data) && in_array($data['has_debts'], ['yes', 'no'])) {
            $report->setHasDebts($data['has_debts']);
            // null debts
            foreach ($report->getDebts() as $debt) {
                $debt->setAmount(null);
                $debt->setMoreDetails(null);
                $this->getEntityManager()->flush($debt);
            }
            // set debts as per "debts" key
            if ($data['has_debts'] == 'yes') {
                foreach ($data['debts'] as $row) {
                    $debt = $report->getDebtByTypeId($row['debt_type_id']);
                    if (!$debt instanceof EntityDir\Report\Debt) {
                        continue; //not clear when that might happen. kept similar to transaction below
                    }
                    $debt->setAmountAndDetails($row['amount'], $row['more_details']);
                    $this->getEntityManager()->flush($debt);
                }
            }
            $this->setJmsSerialiserGroups(['debts']); //returns saved data (AJAX operations)
            $this->getEntityManager()->flush();
            $report->updateSectionsStatusCache([
                Report::SECTION_DEBTS
            ]);
        }

        if (array_key_exists('prof_deputy_other_costs', $data)) {
            $defaultCostTypeIds = array_column($report->getProfDeputyOtherCostTypeIds(), 'typeId');

            foreach ($data['prof_deputy_other_costs'] as $postedProfDeputyOtherCostType) {
                if (in_array(
                    $postedProfDeputyOtherCostType['prof_deputy_other_cost_type_id'],
                        $defaultCostTypeIds
                    )) {
                    $profDeputyOtherCost = $report->getProfDeputyOtherCostByTypeId(
                        $postedProfDeputyOtherCostType['prof_deputy_other_cost_type_id']
                    );

                    // update if exists, or instantiate a new entitys
                    if ($profDeputyOtherCost instanceof EntityDir\Report\ProfDeputyOtherCost) {
                        $profDeputyOtherCost->setAmount($postedProfDeputyOtherCostType['amount']);
                    } else {
                        $profDeputyOtherCost = new EntityDir\Report\ProfDeputyOtherCost(
                            $report,
                            $postedProfDeputyOtherCostType['prof_deputy_other_cost_type_id'],
                            $postedProfDeputyOtherCostType['has_more_details'],
                            $postedProfDeputyOtherCostType['amount']
                        );
                    }
                    if ($profDeputyOtherCost->getHasMoreDetails()) {
                        $profDeputyOtherCost->setMoreDetails($postedProfDeputyOtherCostType['more_details']);
                    }

                    $this->getEntityManager()->persist($profDeputyOtherCost);
                }
            }
            $report->updateSectionsStatusCache([
                Report::SECTION_PROF_DEPUTY_COSTS
            ]);
            $this->getEntityManager()->flush();
        }

        if (array_key_exists('debt_management', $data)) {
            $report->setDebtManagement($data['debt_management']);
            $this->getEntityManager()->flush();
            $report->updateSectionsStatusCache([
                Report::SECTION_DEBTS,
            ]);
        }

        if (array_key_exists('fees', $data)) {
            foreach ($data['fees'] as $row) {
                $fee = $report->getFeeByTypeId($row['fee_type_id']);
                if (!$fee instanceof EntityDir\Report\Fee) {
                    continue; //not clear when that might happen. kept similar to transaction below
                }
                $fee->setAmountAndDetails($row['amount'], $row['more_details']);
                $this->getEntityManager()->flush($fee);
            }
            $report->updateSectionsStatusCache([
                Report::SECTION_DEPUTY_EXPENSES,
                Report::SECTION_PA_DEPUTY_EXPENSES
            ]);
        }

        if (array_key_exists('reason_for_no_fees', $data)) {
            $report->setReasonForNoFees($data['reason_for_no_fees']);
            if ($data['reason_for_no_fees']) {
                foreach ($report->getFees() as $fee) {
                    $fee->setAmount(null)
                        ->setMoreDetails(null);
                }
            }
            $this->getEntityManager()->flush();
            $report->updateSectionsStatusCache([
                Report::SECTION_DEPUTY_EXPENSES,
                Report::SECTION_PA_DEPUTY_EXPENSES
            ]);
        }

        if (array_key_exists('paid_for_anything', $data)) {
            if ($data['paid_for_anything'] === 'no') { // remove existing expenses
                foreach ($report->getExpenses() as $e) {
                    $this->getEntityManager()->remove($e);
                }
            }
            $report->setPaidForAnything($data['paid_for_anything']);
            $this->getEntityManager()->flush();
            $report->updateSectionsStatusCache([
                Report::SECTION_DEPUTY_EXPENSES,
                Report::SECTION_PA_DEPUTY_EXPENSES
            ]);
        }

        if (array_key_exists('gifts_exist', $data)) {
            if ($data['gifts_exist'] === 'no') { // remove existing gift
                foreach ($report->getGifts() as $e) {
                    $this->getEntityManager()->remove($e);
                }
            }
            $report->setGiftsExist($data['gifts_exist']);
            $this->getEntityManager()->flush();
            $report->updateSectionsStatusCache([
                Report::SECTION_GIFTS,
            ]);
        }

        if (array_key_exists('start_date', $data)) {
            $report->setStartDate(new \DateTime($data['start_date']));
        }

        if (array_key_exists('end_date', $data)) {
            $report->setEndDate(new \DateTime($data['end_date']));
            //end date could be updated automatically with a listener, but better not to overload
            // the default behaviour until the logic is 100% clear
            $report->updateDueDateBasedOnEndDate();
        }


        if (array_key_exists('report_seen', $data)) {
            $report->setReportSeen((boolean)$data['report_seen']);
        }

        if (array_key_exists('reason_for_no_contacts', $data)) {
            $report->setReasonForNoContacts($data['reason_for_no_contacts']);
            $report->updateSectionsStatusCache([
                Report::SECTION_CONTACTS,
            ]);
        }


        if (array_key_exists('no_asset_to_add', $data)) {
            $report->setNoAssetToAdd($data['no_asset_to_add']);
            if ($report->getNoAssetToAdd()) {
                foreach ($report->getAssets() as $asset) {
                    $this->getEntityManager()->remove($asset);
                }
                $this->getEntityManager()->flush();
            }
            $report->updateSectionsStatusCache([
                Report::SECTION_ASSETS,
            ]);
        }

        if (array_key_exists('no_transfers_to_add', $data)) {
            if ($data['no_transfers_to_add'] === true) {
                //true here means "no", so remove existing transfers
                foreach ($report->getMoneyTransfers() as $e) {
                    $this->getEntityManager()->remove($e);
                }
            }
            $report->setNoTransfersToAdd($data['no_transfers_to_add']);
            $report->updateSectionsStatusCache([
                Report::SECTION_MONEY_TRANSFERS,
            ]);
        }

        if (array_key_exists('reason_for_no_decisions', $data)) {
            $report->setReasonForNoDecisions($data['reason_for_no_decisions']);
            $report->updateSectionsStatusCache([
                Report::SECTION_DECISIONS,
            ]);
        }

        if (array_key_exists('balance_mismatch_explanation', $data)) {
            $report->setBalanceMismatchExplanation($data['balance_mismatch_explanation']);
            $report->updateSectionsStatusCache([
                Report::SECTION_BALANCE,
            ]);
        }

        if (array_key_exists('action_more_info', $data)) {
            $report->setActionMoreInfo($data['action_more_info']);
            if (array_key_exists('action_more_info_details', $data)) {
                $report->setActionMoreInfoDetails(
                    $data['action_more_info'] == 'yes' ? $data['action_more_info_details'] : null
                );
            }
            $report->updateSectionsStatusCache([
                Report::SECTION_OTHER_INFO,
            ]);
        }

        if (array_key_exists('money_short_categories_in', $data)) {
            foreach ($data['money_short_categories_in'] as $row) {
                $e = $report->getMoneyShortCategoryByTypeId($row['type_id']);
                if ($e instanceof EntityDir\Report\MoneyShortCategory) {
                    $e
                        ->setPresent($row['present']);
                    $this->getEntityManager()->flush($e);
                }
            }
            $this->getEntityManager()->flush();
            $report->updateSectionsStatusCache([
                Report::SECTION_MONEY_IN_SHORT,
                Report::SECTION_MONEY_OUT_SHORT,
            ]);
        }

        if (array_key_exists('money_short_categories_out', $data)) {
            foreach ($data['money_short_categories_out'] as $row) {
                $e = $report->getMoneyShortCategoryByTypeId($row['type_id']);
                if ($e instanceof EntityDir\Report\MoneyShortCategory) {
                    $e
                        ->setPresent($row['present']);
                    $this->getEntityManager()->flush($e);
                }
            }
            $this->getEntityManager()->flush();
            $report->updateSectionsStatusCache([
                Report::SECTION_MONEY_IN_SHORT,
                Report::SECTION_MONEY_OUT_SHORT,
            ]);
        }

        if (array_key_exists('money_transactions_short_in_exist', $data)) {
            if ($data['money_transactions_short_in_exist'] === 'no') { // remove existing
                foreach ($report->getMoneyTransactionsShortIn() as $e) {
                    $this->getEntityManager()->remove($e);
                }
            }
            $report->setMoneyTransactionsShortInExist($data['money_transactions_short_in_exist']);
            $this->getEntityManager()->flush();
            $report->updateSectionsStatusCache([
                Report::SECTION_MONEY_IN_SHORT,
                Report::SECTION_MONEY_OUT_SHORT,
            ]);
        }

        if (array_key_exists('money_transactions_short_out_exist', $data)) {
            if ($data['money_transactions_short_out_exist'] === 'no') { // remove existing
                foreach ($report->getMoneyTransactionsShortOut() as $e) {
                    $this->getEntityManager()->remove($e);
                }
            }
            $report->setMoneyTransactionsShortOutExist($data['money_transactions_short_out_exist']);
            $this->getEntityManager()->flush();
            $report->updateSectionsStatusCache([
                Report::SECTION_MONEY_IN_SHORT,
                Report::SECTION_MONEY_OUT_SHORT,
            ]);
        }

        if (array_key_exists('wish_to_provide_documentation', $data)) {
            $report->setWishToProvideDocumentation($data['wish_to_provide_documentation']);
            if ('no' === $data['wish_to_provide_documentation']) {
                $report->setWishToProvideDocumentation($data['wish_to_provide_documentation']);
            }
            $this->getEntityManager()->flush();
            $report->updateSectionsStatusCache([
                Report::SECTION_DOCUMENTS,
            ]);
        }


        if (array_key_exists('previous_prof_fees_estimate_given', $data)) {
            $report->setPreviousProfFeesEstimateGiven($data['previous_prof_fees_estimate_given']);
            if ($data['previous_prof_fees_estimate_given'] === 'no') {
                $report->setProfFeesEstimateSccoReason(null);
            } else {
                $report->setProfFeesEstimateSccoReason($data['prof_fees_estimate_scco_reason']);
            }
            $this->getEntityManager()->flush();
            $report->updateSectionsStatusCache([
                Report::SECTION_PROF_CURRENT_FEES,
            ]);
        }

        if (array_key_exists('current_prof_payments_received', $data)) {
            if ($data['current_prof_payments_received'] == 'no') { //reset whole section
                foreach ($report->getCurrentProfServiceFees() as $f) {
                    $this->getEntityManager()->remove($f);
                }
                $report->setPreviousProfFeesEstimateGiven(null);
                $report->setProfFeesEstimateSccoReason(null);
            }
            $report->setCurrentProfPaymentsReceived($data['current_prof_payments_received']);
            $this->getEntityManager()->flush();
            $report->updateSectionsStatusCache([
                Report::SECTION_PROF_CURRENT_FEES,
            ]);
        }

        foreach ($this->updateHandlers as $updateHandler) {
            $updateHandler->handle($report, $data);
        }

        $this->getEntityManager()->flush();

        return ['id' => $report->getId()];
    }

    /**
     * @Route("/{id}/unsubmit", requirements={"id":"\d+"}, methods={"PUT"})
     * @Security("has_role('ROLE_CASE_MANAGER')")
     */
    public function unsubmit(Request $request, $id)
    {
        /**
         * @var $report Report
         */
        $report = $this->findEntityBy(EntityDir\Report\Report::class, $id, 'Report not found');
        if (!$report->getSubmitted()) {
            throw new \RuntimeException('Cannot unsubmit an active report');
        }

        $data = $this->deserializeBodyContent($request, [
            'un_submit_date' => 'notEmpty',
            'due_date' => 'notEmpty',
            'unsubmitted_sections_list' => 'notEmpty',
            'start_date' => 'notEmpty',
            'end_date' => 'notEmpty',
        ]);

        $rs = $this->get('opg_digideps.report_service');
        /** @var $rs ReportService */
        $rs->unSubmit(
            $report,
            new \DateTime($data['un_submit_date']),
            new \DateTime($data['due_date']),
            new \DateTime($data['start_date']),
            new \DateTime($data['end_date']),
            $data['unsubmitted_sections_list']
        );

        $this->getEntityManager()->flush();

        return ['id' => $report->getId()];
    }

    /**
     * Update users's reports cached status when not set
     * Flushes every 5 records to allow resuming in case of timeouts
     *
     * @param $userId
     */
    private function updateReportStatusCache($userId)
    {
        $em = $this->get('em');
        $repo = $em->getRepository(Report::class);

        while (($reports = $repo
                ->createQueryBuilder('r')
                ->select('r,c,u')
                ->leftJoin('r.client', 'c')
                ->leftJoin('c.users', 'u')
                ->where('u.id = :uid')
                ->andWhere('r.reportStatusCached IS NULL')
                ->setParameter('uid', $userId)
                ->setMaxResults(5)
                ->getQuery()
                ->getResult()) && count($reports)
        ) {
            foreach ($reports as $report) {
                /* @var $report Report */
                $report->updateSectionsStatusCache($report->getAvailableSections());
            }
            $em->flush();
        }
    }

    /**
     * @Route("/get-all-by-user", methods={"GET"})
     * @Security("has_role('ROLE_ORG')")
     *
     * @param Request $request
     * @return array
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getAllByUser(Request $request)
    {
        return $this->getReponseByDeterminant($request, $this->getUser()->getId(), ReportRepository::USER_DETERMINANT);
    }

    /**
     * @Route("/get-all-by-org", methods={"GET"})
     * @Security("has_role('ROLE_ORG')")
     *
     * @param Request $request
     * @return array
     * @throws \Exception
     */
    public function getAllByOrg(Request $request)
    {
        $organisation = $this->getUser()->getOrganisations()->first();

        if (null === $organisation) {
            throw new NotFoundHttpException('Organisation not found');
        }

        return $this->getReponseByDeterminant($request, $organisation->getId(), ReportRepository::ORG_DETERMINANT);
    }

    /**
     * @param Request $request
     * @param $userId
     * @param int $determinant
     * @return array
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function getReponseByDeterminant(Request $request, $id, int $determinant): array
    {
        $data = $this->repository->getAllByDeterminant($id, $determinant, $request->query, 'reports', $request->query->get('status'));
        $this->updateReportStatusCache($this->getUser()->getId());

        $result = [];
        $result['reports'] = (null === $data) ? [] : $this->transformReports($data);
        $result['counts'] = $this->getReportCountsByStatus($request, $id, $determinant);

        return $result;
    }

    /**
     * @param array $reportData
     * @return array
     */
    private function transformReports(array $reportData): array
    {
        $reports = [];
        foreach ($reportData as $reportDatum) {
            $reports[] = [
                'id' => $reportDatum['id'],
                'type' => $reportDatum['type'],
                'un_submit_date' => $reportDatum['unSubmitDate'] instanceof \DateTime ?
                    $reportDatum['unSubmitDate']->format('Y-m-d') : null,
                'status' => [
                    // adjust report status cached using end date
                    'status' => $this->reportService->adjustReportStatus($reportDatum['reportStatusCached'], $reportDatum['endDate'])
                ],
                'due_date' => $reportDatum['dueDate']->format('Y-m-d'),
                'client' => [
                    'id' => $reportDatum['client']['id'],
                    'firstname' => $reportDatum['client']['firstname'],
                    'lastname' => $reportDatum['client']['lastname'],
                    'case_number' => $reportDatum['client']['caseNumber'],
                ]
            ];
        }

        return $reports;
    }

    /**
     * @param Request $request
     * @param $id
     * @param $determinant
     * @return array
     * @throws \Exception
     */
    private function getReportCountsByStatus(Request $request, $id, $determinant): array
    {

        $counts = [
            Report::STATUS_NOT_STARTED => $this->getCountOfReportsByStatus(Report::STATUS_NOT_STARTED, $id, $determinant, $request),
            Report::STATUS_NOT_FINISHED => $this->getCountOfReportsByStatus(Report::STATUS_NOT_FINISHED, $id, $determinant, $request),
            Report::STATUS_READY_TO_SUBMIT => $this->getCountOfReportsByStatus(Report::STATUS_READY_TO_SUBMIT, $id, $determinant, $request)
        ];

        $counts['total'] = array_sum($counts);

        return $counts;
    }

    /**
     * @param $status
     * @param $orgId
     * @param Request $request
     * @return array|null
     * @throws \Exception
     */
    private function getCountOfReportsByStatus($status, $id, $determinant, Request $request)
    {
        return $this
            ->repository
            ->getAllByDeterminant($id, $determinant, $request->query, 'count', $status);
    }

    /**
     * @Route("/{id}/submit-documents", requirements={"id":"\d+"}, methods={"PUT"})
     * @Security("has_role('ROLE_DEPUTY')")
     */
    public function submitDocuments(Request $request, $id)
    {
        $currentReport = $this->findEntityBy(EntityDir\Report\Report::class, $id, 'Report not found');
        /* @var $currentReport Report */
        $this->denyAccessIfReportDoesNotBelongToUser($currentReport);

        $data = $this->deserializeBodyContent($request);

        // submit and create new year's report
        $report = $this->get('opg_digideps.report_service')
            ->submitAdditionalDocuments($currentReport, $this->getUser(), new \DateTime());

        //response to pass back
        return ['reportId' => $currentReport->getId()];
    }

    /**
     * Add a checklist for the report
     *
     * @Route("/{report_id}/checked", requirements={"report_id":"\d+"}, methods={"POST"})
     * @Security("has_role('ROLE_CASE_MANAGER')")
     */
    public function insertChecklist(Request $request, $report_id)
    {
        $report = $this->findEntityBy(EntityDir\Report\Report::class, $report_id, 'Report not found');

        $checklistData = $this->deserializeBodyContent($request);

        $checklist = new EntityDir\Report\Checklist($report);
        $checklist = $this->populateChecklistEntity($checklist, $checklistData);

        if (!empty($checklistData['further_information_received'])) {
            $info = new EntityDir\Report\ChecklistInformation($checklist, $checklistData['further_information_received']);
            $info->setCreatedBy($this->getUser());
            $this->getEntityManager()->persist($info);
        }

        if ($checklistData['button_clicked'] == 'submitAndDownload') {
            $checklist->setSubmittedBy(($this->getUser()));
            $checklist->setSubmittedOn(new \DateTime());
        }
        $checklist->setLastModifiedBy($this->getUser());

        $this->persistAndFlush($checklist);

        return ['checklist' => $checklist->getId()];
    }

    /**
     * Update a checklist for the report
     *
     * @Route("/{report_id}/checked", requirements={"report_id":"\d+"}, methods={"PUT"})
     * @Security("has_role('ROLE_CASE_MANAGER')")
     */
    public function updateChecklist(Request $request, $report_id)
    {
        $report = $this->findEntityBy(EntityDir\Report\Report::class, $report_id, 'Report not found');

        $checklistData = $this->deserializeBodyContent($request);

        /** @var EntityDir\Report\Checklist $checklist */
        $checklist = $report->getChecklist();

        $checklist = $this->populateChecklistEntity($checklist, $checklistData);

        if (!empty($checklistData['further_information_received'])) {
            $info = new EntityDir\Report\ChecklistInformation($checklist, $checklistData['further_information_received']);
            $info->setCreatedBy($this->getUser());
            $this->getEntityManager()->persist($info);
        }

        if ($checklistData['button_clicked'] == 'submitAndDownload') {
            $checklist->setSubmittedBy(($this->getUser()));
            $checklist->setSubmittedOn(new \DateTime());
        }

        $checklist->setLastModifiedBy($this->getUser());
        $this->persistAndFlush($checklist);

        return ['checklist' => $checklist->getId()];
    }

    private function populateChecklistEntity($checklist, $checklistData)
    {
        $this->hydrateEntityWithArrayData($checklist, $checklistData, [
            'reporting_period_accurate' => 'setReportingPeriodAccurate',
            'contact_details_upto_date' => 'setContactDetailsUptoDate',
            'deputy_full_name_accurate_in_casrec' => 'setDeputyFullNameAccurateInCasrec',
            'decisions_satisfactory' => 'setDecisionsSatisfactory',
            'consultations_satisfactory' => 'setConsultationsSatisfactory',
            'care_arrangements' => 'setCareArrangements',
            'assets_declared_and_managed' => 'setAssetsDeclaredAndManaged',
            'debts_managed' => 'setDebtsManaged',
            'open_closing_balances_match' => 'setOpenClosingBalancesMatch',
            'accounts_balance' => 'setAccountsBalance',
            'money_movements_acceptable' => 'setMoneyMovementsAcceptable',
            'deputy_charge_allowed_by_court' => 'setDeputyChargeAllowedByCourt',
            'satisfied_with_pa_expenses' => 'setSatisfiedWithPaExpenses',
            'bond_adequate' => 'setBondAdequate',
            'satisfied_with_health_and_lifestyle' => 'setSatisfiedWithHealthAndLifestyle',
            'bond_order_match_casrec' => 'setBondOrderMatchCasrec',
            'future_significant_decisions' => 'setFutureSignificantDecisions',
            'has_deputy_raised_concerns' => 'setHasDeputyRaisedConcerns',
            'case_worker_satisified' => 'setCaseWorkerSatisified',
            'payments_match_cost_certificate' => 'setPaymentsMatchCostCertificate',
            'prof_costs_reasonable_and_proportionate' => 'setProfCostsReasonableAndProportionate',
            'has_deputy_overcharged_from_previous_estimates' => 'setHasDeputyOverchargedFromPreviousEstimates',
            'next_billing_estimates_satisfactory' => 'setNextBillingEstimatesSatisfactory',
            'lodging_summary' => 'setLodgingSummary',
            'final_decision' => 'setFinalDecision',
            'button_clicked' => 'setButtonClicked'
        ]);

        return $checklist;
    }

    /**
     * Get a checklist for the report
     * @Route("/{report_id}/checklist", requirements={"report_id":"\d+"}, methods={"GET"})
     * @Security("has_role('ROLE_CASE_MANAGER')")
     */
    public function getChecklist(Request $request, $report_id)
    {
        $this->setJmsSerialiserGroups(['checklist']);

        $checklist = $this
            ->getRepository(EntityDir\Report\ReviewChecklist::class)
            ->findOneBy([ 'report' => $report_id ]);

        return $checklist;
    }

    /**
     * Update a checklist for the report
     *
     * @Route("/{report_id}/checklist", requirements={"report_id":"\d+"}, methods={"PUT"})
     * @Route("/{report_id}/checklist", requirements={"report_id":"\d+"}, methods={"POST"})
     * @Security("has_role('ROLE_CASE_MANAGER')")
     */
    public function upsertChecklist(Request $request, $report_id)
    {
        $report = $this->findEntityBy(EntityDir\Report\Report::class, $report_id, 'Report not found');

        $checklistData = $this->deserializeBodyContent($request);

        $checklist = $this
            ->getRepository(EntityDir\Report\ReviewChecklist::class)
            ->findOneBy([ 'report' => $report->getId() ]);

        if (is_null($checklist))  {
            $checklist = new EntityDir\Report\ReviewChecklist($report);
        }

        $checklist
            ->setAnswers($checklistData['answers'])
            ->setDecision($checklistData['decision']);

        if ($checklistData['submitted']) {
            $checklist->setSubmittedBy(($this->getUser()));
            $checklist->setSubmittedOn(new \DateTime());
        }

        $checklist->setLastModifiedBy($this->getUser());

        $this->persistAndFlush($checklist);

        return ['checklist' => $checklist->getId()];
    }
}
