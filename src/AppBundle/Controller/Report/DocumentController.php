<?php

namespace AppBundle\Controller\Report;

use AppBundle\Controller\AbstractController;
use AppBundle\Entity\Report\Document as Document;
use AppBundle\Form as FormDir;

use AppBundle\Service\File\Checker\Exception\RiskyFileException;
use AppBundle\Service\File\Checker\Exception\VirusFoundException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

class DocumentController extends AbstractController
{
    private static $jmsGroups = [
        'report-documents',
        'documents'
    ];

    /**
     * @Route("/report/{reportId}/documents", name="report_documents")
     * @Template()
     */
    public function indexAction(Request $request, $reportId)
    {
        $fileUploader = $this->get('file_uploader');
        $report = $this->getReportIfNotSubmitted($reportId, self::$jmsGroups);

        // fake documents. remove when the upload is implemented
        $document = new Document();
        $form = $this->createForm(FormDir\Report\DocumentUploadType::class, $document, [
            'action' => $this->generateUrl('report_documents', ['reportId'=>$reportId]) //needed to reset possible JS errors
        ]);
        if ($request->get('error') == 'tooBig') {
            $message = $this->get('translator')->trans('document.fileName.maxSizeMessage', [], 'validators');
            $form->get('file')->addError(new FormError($message));
        }
        $form->handleRequest($request);
        if ($form->isValid()) {
            $uploadedFile = $document->getFile();
            /* @var $uploadedFile UploadedFile */
            try {
                $fileUploader->uploadFile($report, $uploadedFile->getClientOriginalName(), $uploadedFile->getPathname());
            } catch (\Exception $e) {
                $errorToErrorTranslationKey = [
                    RiskyFileException::class => 'risky',
                    VirusFoundException::class => 'virusFound',
                ];
                $errorClass = get_class($e);
                $errorKey = isset($errorToErrorTranslationKey[$errorClass]) ? $errorToErrorTranslationKey[$errorClass] : 'generic';

                $message = $this->get('translator')->trans("form.errors.{$errorKey}", ['%exceptionMessage%' => $e->getMessage()], 'report-documents');
                $form->get('file')->addError(new FormError($message));
            }

            $request->getSession()->getFlashBag()->add('notice', 'File uploaded');
            return $this->redirectToRoute('report_documents', ['reportId' => $reportId]);
        }

        return [
            'report'   => $report,
            'backLink' => $this->generateUrl('report_overview', ['reportId' => $report->getId()]),
            'form'     => $form->createView(),
        ];
    }
}
