<?php

namespace AppBundle\Controller;

use AppBundle\Entity as EntityDir;
use AppBundle\Entity\Report;
use AppBundle\Form as FormDir;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Debug\Debug;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

class DebtController extends AbstractController
{
    /**
     * List debts
     *
     * @Route("/report/{reportId}/debts", name="debts")
     * @Template("AppBundle:Debt:list.html.twig")
     */
    public function listAction(Request $request, $reportId)
    {
        $report = $this->getReport($reportId, ['debts', 'basic', 'client']);
        if ($report->getSubmitted()) {
            throw new \RuntimeException('Report already submitted and not editable.');
        }

        $form = $this->createForm(new FormDir\DebtsType, $report);
        $form->handleRequest($request);

        if ($form->isValid()) {
//            $this->get('restClient')->put('report/'.$report->getId(), $form->getData(), [
//                'deserialise_group' => 'transactionsIn',
//            ]);

            return $this->redirect($this->generateUrl('debts', ['reportId' => $reportId]));
        }

        return [
            'report' => $report,
//            'jsonEndpoint' => 'debts',
            'form' => $form->createView(),
        ];
    }
}
