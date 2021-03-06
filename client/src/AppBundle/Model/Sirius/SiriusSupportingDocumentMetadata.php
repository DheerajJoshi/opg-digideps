<?php declare(strict_types=1);


namespace AppBundle\Model\Sirius;


class SiriusSupportingDocumentMetadata implements SiriusMetadataInterface
{
    /** @var int */
    private $submissionId;

    /**
     * @return int
     */
    public function getSubmissionId(): int
    {
        return $this->submissionId;
    }

    /**
     * @param int $submissionId
     * @return SiriusSupportingDocumentMetadata
     */
    public function setSubmissionId(int $submissionId): self
    {
        $this->submissionId = $submissionId;

        return $this;
    }
}
