<?php

namespace AppBundle\Entity\Traits;

use JMS\Serializer\Annotation as JMS;
use AppBundle\Entity\Report;

trait HasReportTrait
{
    /**
     * @JMS\Type("AppBundle\Entity\Report\Report")
     * @JMS\Groups({"report-object"})
     */
    private $report;

    /**
     * @JMS\VirtualProperty
     * @JMS\Groups({"report-id"})
     *
     * @return int
     */
    public function getReportId()
    {
        return $this->report ? $this->report->getId() : null;
    }

    /**
     * @return Report
     */
    public function getReport()
    {
        return $this->report;
    }

    /**
     * @param Report $report
     */
    public function setReport($report)
    {
        $this->report = $report;

        return $this;
    }
}
