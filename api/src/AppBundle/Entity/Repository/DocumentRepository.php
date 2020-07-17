<?php declare(strict_types=1);

namespace AppBundle\Entity\Repository;

use AppBundle\Entity\Report\Document;
use DateTime;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping;
use PDO;
use Psr\Log\LoggerInterface;

class DocumentRepository extends AbstractEntityRepository
{
    /**
     * Get soft-deleted documents
     *
     * @return Document[]
     */
    public function retrieveSoftDeleted()
    {
        $qb = $this->createQueryBuilder('d')
                ->where('d.deletedAt IS NOT NULL');

        $this->_em->getFilters()->getFilter('softdeleteable')->disableForEntity(Document::class);
        $records = $qb->getQuery()->getResult(); /* @var $records Document[] */
        $this->_em->getFilters()->enable('softdeleteable');

        return $records;
    }

    public function getQueuedDocumentsAndSetToInProgress(string $limit)
    {
        $queuedDocumentsQuery = "
SELECT d.id as document_id,
d.report_submission_id as report_submission_id,
d.is_report_pdf as is_report_pdf,
d.filename as filename,
d.storage_reference as storage_reference,
d.report_id as report_id,
d.ndr_id as ndr_id,
r.start_date as report_start_date,
r.end_date as report_end_date,
r.submit_date as report_submit_date,
r.type as report_type,
rs.opg_uuid as opg_uuid,
rs.created_on as report_submission_created_on,
o.start_date as ndr_start_date,
o.submit_date as ndr_submit_date,
coalesce(c1.case_number, c2.case_number) AS case_number
FROM document as d
LEFT JOIN report as r on d.report_id = r.id
LEFT JOIN odr as o on d.ndr_id = o.id
LEFT JOIN report_submission as rs on d.report_submission_id  = rs.id
LEFT JOIN client as c1 on r.client_id = c1.id
LEFT JOIN client as c2 on o.client_id = c2.id
WHERE synchronisation_status='QUEUED'
ORDER BY report_submission_id
LIMIT $limit;";

        $conn = $this->getEntityManager()->getConnection();

        $docStmt = $conn->prepare($queuedDocumentsQuery);
        $docStmt->execute();

        $documents = [];

        // Get all queued documents
        $results = $docStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($results as $row) {
            $documents[$row['document_id']] = [
                'document_id' => $row['document_id'],
                'document_created_on' => $row['document_created_on'],
                'report_submission_id' => $row['report_submission_id'],
                'ndr_id' => $row['ndr_id'],
                'report_id' => $row['report_id'],
                'report_start_date' => isset($row['report_start_date']) ? $row['report_start_date'] : (new DateTime($row['ndr_start_date']))->format('Y-m-d'),
                'report_end_date' => $row['report_end_date'],
                'report_submit_date' => isset($row['report_submit_date']) ? $row['report_submit_date'] : $row['ndr_submit_date'],
                'report_type' => $row['report_type'],
                'is_report_pdf' => $row['is_report_pdf'],
                'filename' => $row['filename'],
                'storage_reference' => $row['storage_reference'],
                'report_submission_uuid' => $row['opg_uuid'],
                'case_number' => $row['case_number']
            ];


            $reportIds[] = $row['report_id'];
            $ndrIds[] = $row['ndr_id'];
        }

        if (count($documents) > 0) {
            $getReportSubmissionsQuery =  $this->buildReportSubmissionsQuery(
                array_values(array_filter(array_unique($reportIds))),
                array_values(array_filter(array_unique($ndrIds)))
            );

            $submissionStmt = $conn->prepare($getReportSubmissionsQuery);
            $submissionStmt->execute();
            $submissions = $submissionStmt->fetchAll(PDO::FETCH_ASSOC);
            file_put_contents('php://stderr', print_r('$results are: ', TRUE));
            file_put_contents('php://stderr', print_r($submissions, TRUE));

            $reportPdfFlaggedSubmissions = $this->flagSubmissionsContainingReportPdfs($submissions, $conn);
            file_put_contents('php://stderr', print_r('$reportPdfFlaggedSubmissions are: ', TRUE));
            file_put_contents('php://stderr', print_r($reportPdfFlaggedSubmissions, TRUE));

            $groupedSubmissions = $this->groupSubmissionsByReportId($reportPdfFlaggedSubmissions);
            file_put_contents('php://stderr', print_r('$groupedSubmissions are: ', TRUE));
            file_put_contents('php://stderr', print_r($groupedSubmissions, TRUE));

            $groupedSubmissionsWithUuids = $this->assignUuidsToAdditionalDocumentSubmissions($groupedSubmissions);
            file_put_contents('php://stderr', print_r('$groupedSubmissionsWithUuids are: ', TRUE));
            file_put_contents('php://stderr', print_r($groupedSubmissionsWithUuids, TRUE));

            $documentsWithUuids = $this->extractUuidsFromSubmissionsAndAssignToDocuments($documents, $groupedSubmissionsWithUuids);
            file_put_contents('php://stderr', print_r('$documentsWithUuids are: ', TRUE));
            file_put_contents('php://stderr', print_r($documentsWithUuids, TRUE));


            $this->setQueuedDocumentsToInProgress($documentsWithUuids, $conn);

            return $documentsWithUuids;
        }

        return [];
    }

    public function updateSupportingDocumentStatusByReportSubmissionIds(array $reportSubmissionIds, ?string $syncErrorMessage=null)
    {
        $idsString = implode(",", $reportSubmissionIds);
        $status = Document::SYNC_STATUS_PERMANENT_ERROR;

        $updateStatusQuery = "
UPDATE document
SET synchronisation_status = '$status', synchronisation_error = '$syncErrorMessage'
WHERE report_submission_id IN ($idsString)
AND is_report_pdf=false";

        $conn = $this->getEntityManager()->getConnection();
        $stmt = $conn->prepare($updateStatusQuery);
        return $stmt->execute();
    }

    private function flagSubmissionsContainingReportPdfs(array $reportSubmissions, Connection $connection)
    {
        $submissionIds = array_map(function($submission) {
            return $submission['id'];
        }, $reportSubmissions);

        $submissionIdStrings = implode(",", $submissionIds);

        $stmt = $connection->prepare("SELECT * FROM document WHERE report_submission_id IN ($submissionIdStrings) ORDER BY created_on ASC");
        $stmt->execute();
        $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($reportSubmissions as $i => $submission) {
            foreach($documents as $document) {
                if ($document['report_submission_id'] === $submission['id'] && $document['is_report_pdf']) {
                    $reportSubmissions[$i]['contains_report_pdf'] = true;
                    break;
                } else {
                    $reportSubmissions[$i]['contains_report_pdf'] = false;
                }
            }
        }

        return $reportSubmissions;
    }

    private function groupSubmissionsByReportId(array $reportSubmissions)
    {
        $groupedReportSubmissions = ['reports' => [], 'ndrs' => []];

        foreach ($reportSubmissions as $row) {
            if (!is_null($row['report_id'])) {
                $groupedReportSubmissions['reports'][$row['report_id']][] = [
                    'id' => $row['id'],
                    'opg_uuid' => $row['opg_uuid'],
                    'created_on' => $row['created_on'],
                    'report_id' => $row['report_id'],
                    'ndr_id' => $row['ndr_id'],
                ];
            }

            if (!is_null($row['ndr_id'])) {
                $groupedReportSubmissions['ndrs'][$row['ndr_id']][] = [
                    'id' => $row['id'],
                    'opg_uuid' => $row['opg_uuid'],
                    'created_on' => $row['created_on'],
                    'report_id' => $row['report_id'],
                    'ndr_id' => $row['ndr_id'],
                ];
            }
        }

        return $groupedReportSubmissions;
    }

    /**
     * @param array $reportSubmissions
     * @return array
     */
    private function assignUuidsToAdditionalDocumentSubmissions(array $reportSubmissions): array
    {
        $lastUuid = null;
        $lastReportId = null;

//[67] => [
//        [0] => [
//            [id] => 3,
//            [opg_uuid] => 95CBd01A-cea4-CEdB-EaBF-bB709DB9B3cE
//            [created_on] => 2020-07-17 17:32:48
//            [report_id] => 67
//            [ndr_id] =>
//        ]
//
//[1] => [
//        [id] => 4
//        [opg_uuid] =>
//        [created_on] => 2020-07-17 17:36:10
//        [report_id] => 67
//        [ndr_id] =>
//    ]
//]

        // Walk through the submissions grouped by report id to assign missing uuids to additional submissions
        foreach ($reportSubmissions['reports'] as $reportId => $groupedSubmissions) {
            foreach ($groupedSubmissions as $key => $reportSubmission) {
                // We only want to pass on UUIDs associated with a submission containing a report PDF to create correct folders in Sirius
                if (!is_null($reportSubmission['opg_uuid']) && $reportSubmission['contains_report_pdf']) {
                    $lastUuid = $reportSubmission['opg_uuid'];
                    $lastReportId = $reportSubmission['report_id'];
                    continue;
                }

                if (is_null($reportSubmission['opg_uuid']) && $reportSubmission['report_id'] === $lastReportId) {
                    $reportSubmissions['reports'][$reportId][$key]['opg_uuid'] = $lastUuid;
                }
            }
        }

        return $reportSubmissions;
    }

    /**
     * @param array $documents
     * @param array $reportSubmissions
     * @return array
     */
    private function extractUuidsFromSubmissionsAndAssignToDocuments(array $documents, array $reportSubmissions): array
    {
        // Extract the uuids from the submissions and assign to the queued documents data array
        foreach ($documents as $docIndex => $document) {
            if (is_null($document['report_submission_uuid'])) {
                foreach ($reportSubmissions['reports'] as $reportId => $groupedSubmissions) {
                    foreach ($groupedSubmissions as $submission) {
                        if ($document['report_submission_id'] === $submission['id'] ) {
                            $documents[$docIndex]['report_submission_uuid'] = $submission['opg_uuid'];
                            break;
                        }
                    }
                }
            }
        }

        return $documents;
    }

    private function buildReportSubmissionsQuery(array $reportIds, array $ndrIds)
    {
        $reportIdsString = implode(",", $reportIds);
        $ndrIdsString = implode(",", $ndrIds);

        if (count($reportIds) > 0 && count($ndrIds) < 1) {
            return "SELECT * FROM report_submission WHERE (report_id IN ($reportIdsString)) ORDER BY created_on ASC;";
        }

        if (count($ndrIds) > 0 && count($reportIds) < 1) {
            return "SELECT * FROM report_submission WHERE (ndr_id IN ($ndrIdsString)) ORDER BY created_on ASC;";
        }

        if (count($reportIds) > 0 && count($ndrIds) > 0) {
            return "SELECT * FROM report_submission WHERE (report_id IN ($reportIdsString)) OR (ndr_id IN ($ndrIdsString)) ORDER BY created_on ASC;";
        }
    }

    /**
     * @param array $documents
     * @param Connection $connection
     * @throws \Doctrine\DBAL\DBALException
     */
    private function setQueuedDocumentsToInProgress(array $documents, Connection $connection): void
    {
        if (count($documents)) {
            // Set documents to in progress to ensure additional runs won't pick up the same documents
            $ids = [];
            foreach ($documents as $data) {
                $ids[] = $data['document_id'];

                $idsString = implode(",", $ids);

                $updateStatusQuery = "UPDATE document SET synchronisation_status = 'IN_PROGRESS' WHERE id IN ($idsString)";
                $stmt = $connection->prepare($updateStatusQuery);

                $stmt->execute();
            }
        }
    }
}
