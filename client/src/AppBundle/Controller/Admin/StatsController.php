<?php

namespace AppBundle\Controller\Admin;

use AppBundle\Controller\AbstractController;
use AppBundle\Exception\DisplayableException;
use AppBundle\Form\Admin\ReportSubmissionDownloadFilterType;
use AppBundle\Form\Admin\StatPeriodType;
use AppBundle\Mapper\ReportSubmission\ReportSubmissionSummaryQuery;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/admin/stats")
 */
class StatsController extends AbstractController
{
    /**
     * @Route("", name="admin_stats")
     * @Security("has_role('ROLE_ADMIN') or has_role('ROLE_AD')")
     * @Template("Admin/Stats/stats.html.twig")
     * @param Request $request
     * @return array|Response
     */
    public function statsAction(Request $request)
    {
        $form = $this->createForm(ReportSubmissionDownloadFilterType::class , new ReportSubmissionSummaryQuery());
        $form->handleRequest($request);

        if ($form->isValid()) {
            try {

                $mapper = $this->get('mapper.report_submission_summary_mapper');
                $transformer = $this->get('transformer.report_submission_bur_fixed_width_transformer');

                $reportSubmissionSummaries = $mapper->getBy($form->getData());
                $downloadableData = $transformer->transform($reportSubmissionSummaries);

                return $this->buildResponse($downloadableData);

            } catch (\Throwable $e) {
                throw new DisplayableException($e);
            }
        }

        return [
            'form' => $form->createView()
        ];
    }

    /**
     * @param $csvContent
     * @return Response
     */
    private function buildResponse($csvContent)
    {
        $response = new Response($csvContent);
        $response->headers->set('Content-Type', 'application/octet-stream');

        $attachmentName = sprintf('cwsdigidepsopg00001%s.dat', date('YmdHi'));
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $attachmentName . '"');

        $response->sendHeaders();

        return $response;
    }

    /**
     * @Route("/metrics", name="admin_metrics")
     * @Security("has_role('ROLE_ADMIN') or has_role('ROLE_AD')")
     * @Template("Admin/Stats/metrics.html.twig")
     * @param Request $request
     * @return array|Response
     */
    public function metricsAction(Request $request)
    {
        $form = $this->createForm(StatPeriodType::class);
        $form->handleRequest($request);

        $append = '';

        if ($form->isValid()) {
            $startDate = $form->get('startDate')->getData();
            $endDate = $form->get('endDate')->getData();
            $append = "&startDate={$startDate->format('Y-m-d')}&endDate={$endDate->format('Y-m-d')}";
        }

        $metrics = ['satisfaction', 'reportsSubmitted', 'clients', 'registeredDeputies'];

        foreach ($metrics as $metric) {
            $all = $this->getRestClient()->get('stats?metric=' . $metric . $append, 'array');
            $byRole = $this->getRestClient()->get('stats?metric=' . $metric . '&dimension[]=deputyType' . $append, 'array');

            $stats[$metric] = array_merge(
                ['all' => $all[0]['amount']],
                $this->mapToDeputyType($byRole)
            );
        }

        return [
            'stats' => $stats,
            'form' => $form->createView()
        ];
    }

    /**
     * Map an array of metric responses to be addressible by deputyType
     */
    private function mapToDeputyType(array $result): array {
        $resultByDeputyType = [];

        foreach ($result as $resultBit) {
            $resultByDeputyType[$resultBit['deputyType']] = $resultBit['amount'];
        }

        return $resultByDeputyType;
    }
}
