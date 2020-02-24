<?php

namespace AppBundle\Entity\Report;

use AppBundle\Entity\Ndr\Ndr;
use AppBundle\Entity\ReportInterface;
use AppBundle\Entity\User;
use AppBundle\Entity\Traits\CreationAudit;
use DateTime;
use JMS\Serializer\Annotation as JMS;
use RuntimeException;

class ReportSubmission
{
    use CreationAudit;

    /**
     * @var int
     *
     * @JMS\Type("integer")
     */
    private $id;

    /**
     * @var Report
     *
     * @JMS\Type("AppBundle\Entity\Report\Report")
     */
    private $report;

    /**
     * @var Ndr|null
     *
     * @JMS\Type("AppBundle\Entity\Ndr\Ndr")
     */
    private $ndr;

    /**
     * @JMS\Type("array<AppBundle\Entity\Report\Document>")
     */
    private $documents = [];

    /**
     * @var User
     *
     * @JMS\Type("AppBundle\Entity\User")
     */
    private $archivedBy;

    /**
     * @var bool
     *
     * @JMS\Type("boolean")
     */
    private $downloadable;

    /**
     * @var string|null
     * @JMS\Type("string")
     */
    private $uuid;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param  int              $id
     * @return ReportSubmission
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return Report|null
     */
    public function getReport()
    {
        return $this->report;
    }

    /**
     * @param  Report           $report
     * @return ReportSubmission
     */
    public function setReport($report)
    {
        $this->report = $report;

        return $this;
    }

    /**
     * @return Ndr|null
     */
    public function getNdr()
    {
        return $this->ndr;
    }

    /**
     * @param  Ndr           $ndr
     * @return ReportSubmission
     */
    public function setNdr($ndr)
    {
        $this->ndr = $ndr;

        return $this;
    }

    /**
     * @return Document[]
     */
    public function getDocuments()
    {
        return $this->documents;
    }

    /**
     * @param  array            $documents
     * @return ReportSubmission
     */
    public function setDocuments($documents)
    {
        $this->documents = $documents;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasReportPdf()
    {
        foreach ($this->documents as $document) {
            if($document->isReportPdf()) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return User
     */
    public function getArchivedBy()
    {
        return $this->archivedBy;
    }

    /**
     * @param  User             $archivedBy
     * @return ReportSubmission
     */
    public function setArchivedBy($archivedBy)
    {
        $this->archivedBy = $archivedBy;

        return $this;
    }

    /**
     * @return bool
     */
    public function isDownloadable()
    {
        return $this->downloadable;
    }

    /**
     * @param bool $downloadable
     *
     * @return ReportSubmission
     */
    public function setDownloadable($downloadable)
    {
        $this->downloadable = $downloadable;

        return $this;
    }

    /**
     * @return string
     */
    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    /**
     * @param string|null $uuid
     *
     * @return $this
     */
    public function setUuid(?string $uuid)
    {
        $this->uuid = $uuid;

        return $this;
    }

    /**
     * @return string
     */
    public function getZipName()
    {
        $report = $this->getReport() ? $this->getReport() : $this->getNdr();

        if (is_null($report)) {
            throw new RuntimeException('Report submission has no associated report');
        }

        $client = $report->getClient();

        if ($report instanceof Ndr) {
            return 'NdrReport-'
                . $client->getCaseNumber()
                . '_' . $report->getStartDate()->format('Y')
                . '_' . $this->getId()
                . '.zip';
        } else {
            /** @var DateTime $startDate */
            $startDate = $report->getStartDate();
            /** @var DateTime $endDate */
            $endDate = $report->getEndDate();

            return 'Report_'
                . $client->getCaseNumber()
                . '_' . $startDate->format('Y')
                . '_' . $endDate->format('Y')
                . '_' . $this->getId()
                . '.zip';
        }
    }
}
