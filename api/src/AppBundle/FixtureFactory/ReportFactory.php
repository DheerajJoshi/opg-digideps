<?php

namespace AppBundle\FixtureFactory;

use AppBundle\Entity\Client;
use AppBundle\Entity\Report\Report;
use AppBundle\Entity\User;
use AppBundle\v2\Fixture\ReportSection;

class ReportFactory
{
    /** @var ReportSection  */
    private $reportSection;

    /**
     * @param ReportSection $reportSection
     */
    public function __construct(ReportSection $reportSection)
    {
        $this->reportSection = $reportSection;
    }

    /**
     * @param array $data
     * @param Client $client
     * @return Report
     * @throws \Exception
     */
    public function create(array $data, Client $client): Report
    {
        if ($data['deputyType'] === User::TYPE_LAY) {
            $type = $data['reportType'];
        } else if ($data['deputyType'] === User::TYPE_PA) {
            $type = $data['reportType'] . '-6';
        } else if ($data['deputyType'] === User::TYPE_PROF) {
            $type = $data['reportType'] . '-5';
        }

        $startDate = $client->getExpectedReportStartDate($client->getCourtDate()->format('Y'));
        $endDate = $client->getExpectedReportEndDate($client->getCourtDate()->format('Y'));

        $report = new Report($client, $type, $startDate, $endDate);

        if (isset($data['reportStatus']) && $data['reportStatus'] === Report::STATUS_READY_TO_SUBMIT) {
            $this->reportSection->completeReport($report);
            $report->updateSectionsStatusCache($report->getAvailableSections());
        }

        return $report;
    }
}
