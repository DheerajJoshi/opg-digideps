<?php

namespace AppBundle\Service\File;

use AppBundle\Entity\Report\Document;
use AppBundle\Entity\Report\Report;
use AppBundle\Entity\Report\ReportSubmission;
use AppBundle\Service\DocumentService;
use AppBundle\Service\File\Storage\FileNotFoundException;
use AppBundle\Service\File\Storage\S3Storage;
use AppBundle\Service\File\Storage\StorageInterface;
use Aws\Command;
use Aws\S3\Exception\S3Exception;
use Doctrine\Common\Collections\ArrayCollection;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use ZipArchive;

class DocumentZipFileCreatorTest extends TestCase
{
    /**
     * @var DocumentsZipFileCreator
     */
    private $object;

    public function setUp(): void
    {
        $this->storage = m::mock(StorageInterface::class);
        $this->reportSubmission = m::mock(ReportSubmission::class, [
            'getDocuments' => [],
        ]);

        $this->object = new DocumentsZipFileCreator($this->reportSubmission, $this->storage);
    }

    public function testcreateZipFileNoDocuments()
    {
        $this->reportSubmission->shouldReceive('isDownloadable')->once()->withNoArgs()->andReturn(true);
        $this->reportSubmission->shouldReceive('getDocuments')->andReturn([]);

        $this->expectException('RuntimeException');
        $this->object->createZipFile();
    }

    public function testcreateZipFileNotDownloadable()
    {
        $this->reportSubmission->shouldReceive('isDownloadable')->once()->withNoArgs()->andReturn(false);

        $this->expectException('RuntimeException', DocumentsZipFileCreator::MSG_NOT_DOWNLOADABLE);
        $this->object->createZipFile();
    }

    public function testcreateZipFile()
    {
        $zipFileName = 'Report_12345678_2017_2018.zip';
        $doc1 = m::mock(Document::class, [
            'getId' => 1,
            'getFileName' => 'file1.pdf',
            'getStorageReference' => 'r1'
        ]);
        $doc2 = m::mock(Document::class, [
            'getId' => 2,
            'getFileName' => 'file2.pdf',
            'getStorageReference' => 'r2'
        ]);
        $this->storage->shouldReceive('retrieve')->with('r1')->andReturn('doc1-content');
        $this->storage->shouldReceive('retrieve')->with('r2')->andReturn('doc2-content');

        $this->reportSubmission
            ->shouldReceive('getZipName')->andReturn($zipFileName)
            ->shouldReceive('getDocuments')->andReturn([$doc1, $doc2])
            ->shouldReceive('isDownloadable')->once()->withNoArgs()->andReturn(true)
        ;

        $fileName = $this->object->createZipFile();

        $this->assertStringContainsString($zipFileName, $fileName);
        $this->assertEquals('doc1-content', exec("unzip -c $fileName file1.pdf"));
        $this->assertEquals('doc2-content', exec("unzip -c $fileName file2.pdf"));

        $this->object->cleanUp();

        $this->assertFalse(file_exists($fileName));
    }

    /**
     * @group acs
     */
    public function testGracefullyHandleMissingFiles()
    {
        $doc1 = self::prophesize(Document::class);
        $doc1->getStorageReference()->willReturn('ref-1');
        $doc1->getId()->willReturn(1);
        $doc1->getFileName()->willReturn('file-name1.pdf');

        $doc2 = self::prophesize(Document::class);
        $doc2->getStorageReference()->willReturn('ref-2');
        $doc2->getId()->willReturn(2);
        $doc2->getFileName()->willReturn('file-name2.pdf');

        $doc3 = self::prophesize(Document::class);
        $doc3->getStorageReference()->willReturn('ref-3');
        $doc3->getId()->willReturn(3);
        $doc3->getFileName()->willReturn('file-name3.pdf');

        /** @var ObjectProphecy|ReportSubmission $reportSubmission */
        $reportSubmission = self::prophesize(ReportSubmission::class);
        $reportSubmission->isDownloadable()->willReturn(true);
        $reportSubmission->getDocuments()->willReturn(new ArrayCollection([$doc1->reveal(), $doc2->reveal(), $doc3->reveal()]));
        $reportSubmission->getZipName()->willReturn('Report_12345678_2017_2018.zip');

        $documentsContents = ['file-name1.pdf' => 'doc1 content', 'file-name3.pdf' => 'doc3 content'];
        $sut = new DocumentsZipFileCreator();

        $fileName = $sut->createZipFileFromDocumentContents($documentsContents, $reportSubmission->reveal());

        self::assertTrue(file_exists($fileName));

        $zip = new ZipArchive();
        $zip->open($fileName);

        self::assertEquals(2, $zip->numFiles);
    }

    /**
     * @group acs
     */
    public function testcreateMultiZipFile()
    {
        $sut = new DocumentsZipFileCreator();

        $zipFileContents = ['some content', 'some different content'];
        $zipFiles = [];

        $zip = new ZipArchive();

        foreach ($zipFileContents as $content) {
            $document = $sut::TMP_ROOT_PATH . "test-" . microtime(1);
            file_put_contents($document, $content);

            $zip->open($document, ZipArchive::CREATE | ZipArchive::OVERWRITE | ZipArchive::CHECKCONS);
            $zip->addFile($document, $document);

            $zipFiles[] = $document;

            $zip->close();
        }

        $zippedZipFiles = $sut->createMultiZipFile($zipFiles);

        $zip->open($zippedZipFiles);

        self::assertEquals(2, $zip->numFiles);

        $zip->close();
    }

    public function tearDown(): void
    {
        m::close();
    }
}
