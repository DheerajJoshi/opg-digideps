<?php

namespace AppBundle\Controller\Report;

use AppBundle\Controller\RestController;
use AppBundle\Entity as EntityDir;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/report-submission")
 */
class ReportSubmissionController extends RestController
{
    private static $jmsGroups = [
        'report-submission',
        'report-type',
        'report-client',
        'ndr-client',
        'ndr',
        'report-period',
        'client-name',
        'client-case-number',
        'client-email',
        'client-discharged',
        'user-name',
        'user-rolename',
        'user-teamname',
        'documents',
    ];

    /**
     * @Route("", methods={"GET"})
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function getAll(Request $request)
    {
        $repo = $this->getRepository(EntityDir\Report\ReportSubmission::class); /* @var $repo EntityDir\Repository\ReportSubmissionRepository */

        $ret = $repo->findByFiltersWithCounts(
                $request->get('status'),
                $request->get('q'),
                $request->get('created_by_role'),
                $request->get('offset', 0),
                $request->get('limit', 15),
                $request->get('orderBy', 'createdOn'),
                $request->get('order', 'ASC')
            );

        $this->setJmsSerialiserGroups(self::$jmsGroups);

        return $ret;
    }

    /**
     * @Route("/{id}", requirements={"id":"\d+"}, methods={"GET"})
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function getOneById(Request $request, $id)
    {
        $ret = $this->getRepository(EntityDir\Report\ReportSubmission::class)->findOneByIdUnfiltered($id);

        $this->setJmsSerialiserGroups(array_merge(self::$jmsGroups, ['document-storage-reference']));

        return $ret;
    }

    /**
     * Update documents
     * return array of storage references, for admin area to delete if needed
     *
     * @Route("/{reportSubmissionId}", requirements={"reportSubmissionId":"\d+"}, methods={"PUT"})
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function update(Request $request, $reportSubmissionId)
    {
        /* @var $reportSubmission EntityDir\Report\ReportSubmission */
        $reportSubmission = $this->findEntityBy(EntityDir\Report\ReportSubmission::class, $reportSubmissionId);

        $data = $this->deserializeBodyContent($request);
        if (!empty($data['archive'])) {
            $reportSubmission->setArchivedBy($this->getUser());
        }

        if (!empty($data['uuid'])) {
            $reportSubmission->setUuid($data['uuid']);
        }

        $this->getEntityManager()->flush();

        return $reportSubmission->getId();
    }

    /**
     * Get old report submissions.
     * Called from ADMIN cron
     *
     * @Route("/old", methods={"GET"})
     */
    public function getOld(Request $request)
    {
        if (!$this->getAuthService()->isSecretValidForRole(EntityDir\User::ROLE_ADMIN, $request)) {
            throw new \RuntimeException(__METHOD__ . ' only accessible from ADMIN container.', 403);
        }

        $repo = $this->getRepository(EntityDir\Report\ReportSubmission::class); /* @var $repo EntityDir\Repository\ReportSubmissionRepository */

        $ret = $repo->findDownloadableOlderThan(new \DateTime(EntityDir\Report\ReportSubmission::REMOVE_FILES_WHEN_OLDER_THAN), 100);

        $this->setJmsSerialiserGroups(['report-submission-id', 'report-submission-documents', 'document-storage-reference']);

        return $ret;
    }

    /**
     * Set report undownloadable (and remove the storage reference for the files.
     * Called from ADMIN cron
     *
     * @Route("/{id}/set-undownloadable", requirements={"id":"\d+"}, methods={"PUT"})
     */
    public function setUndownloadable($id, Request $request)
    {
        if (!$this->getAuthService()->isSecretValidForRole(EntityDir\User::ROLE_ADMIN, $request)) {
            throw new \RuntimeException(__METHOD__ . ' only accessible from ADMIN container.', 403);
        }

        /* @var $reportSubmission EntityDir\Report\ReportSubmission */
        $reportSubmission = $this->getRepository(EntityDir\Report\ReportSubmission::class)->find($id);
        $reportSubmission->setDownloadable(false);
        foreach ($reportSubmission->getDocuments() as $document) {
            $document->setStorageReference(null);
        }

        $this->getEntityManager()->flush();

        return true;
    }

    /**
     * @Route("/casrec_data", name="casrec_data", methods={"GET"})
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function getCasrecData(Request $request)
    {
        /* @var $repo EntityDir\Repository\ReportSubmissionRepository */
        $repo = $this->getRepository(EntityDir\Report\ReportSubmission::class);

        $ret = $repo->findAllReportSubmissions(
            $this->convertDateArrayToDateTime($request->get('fromDate', [])),
            $this->convertDateArrayToDateTime($request->get('toDate', [])),
            $request->get('orderBy', 'createdOn'),
            $request->get('order', 'ASC')
        );

        return $this->get('app.transformer.report_submission.report_submission_summary_transformer')->transform($ret);
    }

    /**
     * @param array $date
     * @return \DateTime|null
     */
    private function convertDateArrayToDateTime(array $date)
    {
        return (isset($date['date'])) ? new \DateTime($date['date']) : null;
    }
}
