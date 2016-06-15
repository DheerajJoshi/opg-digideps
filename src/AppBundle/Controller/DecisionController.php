<?php

namespace AppBundle\Controller;

use AppBundle\Entity as EntityDir;
use AppBundle\Form as FormDir;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

class DecisionController extends AbstractController
{
    /**
     * @Route("/report/{reportId}/decisions", name="decisions")
     * @Template("AppBundle:Decision:list.html.twig")
     *
     * @param int $reportId
     *
     * @return array
     */
    public function listAction($reportId)
    {
        $report = $this->getReportIfReportNotSubmitted($reportId, ['transactions', 'basic', 'client', 'decisions']);

        $decisions = $report->getDecisions();

        if (empty($decisions) && $report->isDue() == false) {
            return $this->redirect($this->generateUrl('add_decision', ['reportId' => $reportId]));
        }

        return [
            'decisions' => $decisions,
            'report' => $report,
        ];
    }

    /**
     * @Route("/report/{reportId}/decisions/add", name="add_decision")
     * @Template("AppBundle:Decision:add.html.twig")
     */
    public function addAction(Request $request, $reportId)
    {
        $report = $this->getReportIfReportNotSubmitted($reportId, ['transactions', 'basic', 'client']);

        $decision = new EntityDir\Decision();
        $decision->setReport($report);
        $form = $this->createForm(new FormDir\DecisionType(), $decision);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $data = $form->getData();
            $data->setReport($report);

            $this->getRestClient()->post('report/decision', $data, [
                'deserialise_group' => 'Default',
            ]);

            return $this->redirect($this->generateUrl('decisions', ['reportId' => $reportId]));
        }

        return [
            'form' => $form->createView(),
            'report' => $report,
        ];
    }

    /**
     * @Route("/report/{reportId}/decisions/{id}/edit", name="edit_decision")
     * @Template("AppBundle:Decision:edit.html.twig")
     */
    public function editAction(Request $request, $reportId, $id)
    {
        $report = $this->getReportIfReportNotSubmitted($reportId, ['transactions', 'basic', 'client']);

        $decision = $this->getRestClient()->get('report/decision/'.$id, 'Decision');

        $form = $this->createForm(new FormDir\DecisionType(), $decision);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $data = $form->getData();
            $data->setReport($report);

            $this->getRestClient()->put('report/decision', $data, [
                 'deserialise_group' => 'Default',
            ]);

            return $this->redirect($this->generateUrl('decisions', ['reportId' => $reportId]));
        }

        return [
            'form' => $form->createView(),
            'report' => $report,
        ];
    }

    /**
     * @Route("/report/{reportId}/decisions/{id}/delete", name="delete_decision")
     *
     * @param int $id
     * 
     * @return RedirectResponse
     */
    public function deleteAction($reportId, $id)
    {
        //just do some checks to make sure user is allowed to delete this contact
        $report = $this->getReport($reportId, ['basic', 'decisions']);

        foreach ($report->getDecisions() as $decision) {
            if ($decision->getId() == $id) {
                $this->getRestClient()->delete("/report/decision/{$id}");
            }
        }

        return $this->redirect($this->generateUrl('decisions', ['reportId' => $reportId]));
    }

    /**
     * @Route("/report/{reportId}/decisions/delete-nonereason", name="delete_nonereason_decisions")
     */
    public function deleteReasonAction($reportId)
    {
        //just do some checks to make sure user is allowed to update this report
        $report = $this->getReport($reportId, ['basic', 'transactions']);

        if (!empty($report)) {
            $report->setReasonForNoDecisions(null);
            $this->getRestClient()->put('report/'.$report->getId(), $report, [
                'deserialise_group' => 'reasonForNoDecisions'
            ]);
        }

        return $this->redirect($this->generateUrl('decisions', ['reportId' => $report->getId()]));
    }

    /**
     * @Route("/report/{reportId}/decisions/nonereason", name="edit_decisions_nonereason")
     * @Template("AppBundle:Decision:edit_none_reason.html.twig")
     */
    public function noneReasonAction(Request $request, $reportId)
    {
        $report = $this->getReportIfReportNotSubmitted($reportId, ['transactions', 'basic', 'client']);
        $form = $this->createForm(new FormDir\ReasonForNoDecisionType(), $report);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $data = $form->getData();
            $this->getRestClient()->put('report/'.$reportId, $data, [
                'deserialise_group' => 'reasonForNoDecisions'
            ]);

            return $this->redirect($this->generateUrl('decisions', ['reportId' => $reportId]));
        }

        return [
            'form' => $form->createView(),
            'report' => $report,
        ];
    }

    /**
     * Sub controller action called when the no decision form is embedded in another page.
     *
     * @Template("AppBundle:Decision:_none_reason_form.html.twig")
     */
    public function _noneReasonFormAction(Request $request, $reportId)
    {
        $actionUrl = $this->generateUrl('edit_decisions_nonereason', ['reportId' => $reportId]);
        $report = $this->getReportIfReportNotSubmitted($reportId, ['transactions', 'basic', 'client']);
        $form = $this->createForm(new FormDir\ReasonForNoDecisionType(), $report, ['action' => $actionUrl]);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $data = $form->getData();
            $this->getRestClient()->put('report/'.$reportId, $data, [
                'deserialise_group' => 'reasonForNoDecisions'
            ]);
        }

        return [
            'form' => $form->createView(),
            'report' => $report,
        ];
    }
}
