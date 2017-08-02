<?php

namespace AppBundle\Service\File;

use AppBundle\Entity\Report\Document;
use AppBundle\Entity\Report\Report;
use AppBundle\Service\Client\RestClient;
use AppBundle\Service\File\Checker\FileCheckerInterface;
use AppBundle\Service\File\Storage\StorageInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileUploader
{
    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * @var RestClient
     */
    private $restClient;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var array
     */
    private $options;

    /**
     * FileUploader constructor.
     */
    public function __construct(StorageInterface $s3Storage, RestClient $restClient, LoggerInterface $logger, array $options = [])
    {
        $this->storage = $s3Storage;
        $this->restClient = $restClient;
        $this->logger = $logger;
        $this->fileCheckers = [];
        $this->options = [];
    }

    /**
     * Uploads a file and return the created document
     * might throw exceptions if viruses are found. File is immediately deleted in that case
     *
     * @return Document
     *
     * code imported from
     * https://github.com/ministryofjustice/opg-av-test/blob/master/public/index.php
     */
    public function uploadFile(Report $report, UploadedFile $uploadedFile)
    {
        $body = file_get_contents($uploadedFile->getPathName());

        $storageReference = 'dd_doc_' . $report->getId() . '_' . str_replace('.', '', microtime(1));
        $this->storage->store($storageReference, $body);
        $this->logger->debug("Stored file, reference = $storageReference, size " . strlen($body));
        $document = new Document();
        $document->setStorageReference($storageReference)->setFileName($uploadedFile->getClientOriginalName());
        $this->restClient->post('/report/' . $report->getId() . '/document', $document, ['document']);

        return $document;
    }
}
