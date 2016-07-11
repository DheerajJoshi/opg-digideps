<?php

namespace AppBundle\Controller\Odr;

use AppBundle\Entity as EntityDir;
use AppBundle\Form as FormDir;
use AppBundle\Model as ModelDir;
use AppBundle\Service\OdrStatusService;
use AppBundle\Service\ReportStatusService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\TranslatorInterface;
use AppBundle\Controller\AbstractController;

class IndexController extends AbstractController
{
    private static $odrGroupsForValidation = [
        'odr',
        'visits-care',
        'odr-account',
    ];

    /**
     * //TODO move view into Odr directory when branches are integrated
     * @Route("/odr", name="odr_index")
     * @Template()
     */
    public function indexAction()
    {
        $client = $this->getClientOrThrowException();
        $client = $this->getRestClient()->get('client/'.$client->getId(), 'Client');
        $odr = $this->getOdr($client->getOdr()->getId(), self::$odrGroupsForValidation);
        $odr->setClient($client);

        $reports = $client ? $this->getReportsIndexedById($client, ['basic']) : [];
        //arsort($reports);

        $reportActive = null;
        $reportsSubmitted = [];
        foreach($reports as $currentReport) {
            if ($currentReport->getSubmitted()) {
                $reportsSubmitted[] = $currentReport;
            } else {
                $reportActive = $currentReport;
            }
        }

        $odrStatus = new OdrStatusService($odr);

        return [
            'client' => $odr->getClient(),
            'odr' => $odr,
            'reportsSubmitted' => $reportsSubmitted,
            'reportActive' => $reportActive,
            'odrStatus' => $odrStatus,
        ];
    }

    /**
     * @Route("/odr/overview", name="odr_overview")
     * @Template()
     */
    public function overviewAction()
    {
        $client = $this->getClientOrThrowException();
        $client = $this->getRestClient()->get('client/'.$client->getId(), 'Client');
        $odr = $this->getOdr($client->getOdr()->getId(), self::$odrGroupsForValidation);
        $odr->setClient($client);

        if ($odr->getSubmitted()) {
            throw new \RuntimeException('Odr already submitted and not editable.');
        }
        $odrStatus = new OdrStatusService($odr);

        return [
            'client' => $client,
            'odr' => $odr,
            'odrStatus' => $odrStatus,
        ];
    }

    /**
     * @Route("/odr/submit", name="odr_submit")
     * @Template()
     */
    public function submitAction(Request $request)
    {
        $client = $this->getClientOrThrowException();
        $client = $this->getRestClient()->get('client/'.$client->getId(), 'Client');
        $odr = $this->getOdr($client->getOdr()->getId(), self::$odrGroupsForValidation);
        $odr->setClient($client);

        if ($odr->getSubmitted()) {
            throw new \RuntimeException('ODR already submitted and not editable.');
        }

        $odr->setSubmitted(true)->setSubmitDate(new \DateTime());
        $this->getRestClient()->put('odr/' . $odr->getId() . '/submit', $odr, [
            'deserialise_group' => 'submit',
        ]);

        return $this->redirect($this->generateUrl('odr_index'));
    }

    /**
     * Used for active and archived ODRs.
     *
     * @Route("/odr/{odrId}/review", name="odr_review")
     * @Template()
     */
    public function reviewAction($odrId)
    {
        $client = $this->getClientOrThrowException();
        $client = $this->getRestClient()->get('client/'.$client->getId(), 'Client');
        $odr = $this->getOdr($client->getOdr()->getId(), self::$odrGroupsForValidation);
        $odr->setClient($client);

        // check status
        $odrStatusService = new OdrStatusService($odr);

        return [
            'odr' => $odr,
            'deputy' => $this->getUser(),
            'odrStatus' => $odrStatusService,
        ];
    }

    /**
     * @Route("/odr/deputyodr-{odrId}.pdf", name="odr_pdf")
     */
    public function pdfViewAction($odrId)
    {
        $client = $this->getClientOrThrowException();
        $client = $this->getRestClient()->get('client/'.$client->getId(), 'Client');
        $odr = $this->getOdr($client->getOdr()->getId(), self::$odrGroupsForValidation);
        $odr->setClient($client);

        $pdfBinary = $this->getPdfBinaryContent($odr);

        $response = new Response($pdfBinary);
        $response->headers->set('Content-Type', 'application/pdf');

        $attachmentName = sprintf('DigiOdr-%s_%s.pdf',
            $odr->getSubmitDate() ? $odr->getSubmitDate()->format('Y-m-d') : 'n-a-',
            $odr->getClient()->getCaseNumber()
        );

        $response->headers->set('Content-Disposition', 'attachment; filename="'.$attachmentName.'"');
//        $response->headers->set('Content-length', strlen($->getSize()); // not easy to calculate binary size in bytes

        // Send headers before outputting anything
        $response->sendHeaders();

        return $response;
    }


    private function getPdfBinaryContent($odr)
    {
        $html = $this->render('AppBundle:Odr/Formatted:formatted_body.html.twig', array(
            'odr' => $odr,
        ))->getContent();

        return $this->get('wkhtmltopdf')->getPdfFromHtml($html);
    }
}
