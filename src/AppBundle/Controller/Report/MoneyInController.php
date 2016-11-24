<?php

namespace AppBundle\Controller\Report;

use AppBundle\Controller\AbstractController;
use AppBundle\Entity as EntityDir;
use AppBundle\Form as FormDir;
use AppBundle\Service\ReportStatusService;
use AppBundle\Service\StepRedirector;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class MoneyInController extends AbstractController
{
    const STEPS = 3;

    /**
     * @Route("/report/{reportId}/money-in/start", name="money_in")
     * @Template()
     */
    public function startAction(Request $request, $reportId)
    {
        $report = $this->getReportIfReportNotSubmitted($reportId, ['account']);
        if ($report->hasMoneyIn()) {
            return $this->redirectToRoute('money_in_summary', ['reportId' => $reportId]);
        }

        return [
            'report' => $report,
        ];
    }

    /**
     * //TODO refactor when assets is implemented too
     *
     * @Route("/report/{reportId}/money-in/step{step}/{transactionId}", name="money_in_step", requirements={"step":"\d+"})
     * @Template()
     */
    public function stepAction(Request $request, $reportId, $step, $transactionId = null)
    {
        if ($step < 1 || $step > self::STEPS) {
            return $this->redirectToRoute('money_in_summary', ['reportId' => $reportId]);
        }

        // common vars and data
        $dataFromUrl = $request->get('data') ?: [];
        $stepUrlData = $dataFromUrl;
        $report = $this->getReportIfReportNotSubmitted($reportId, ['transaction']);
        $fromPage = $request->get('from');

        /* @var $stepRedirector StepRedirector */
        $stepRedirector = $this->get('stepRedirector')
            ->setRoutePrefix('money_in_')
            ->setFromPage($fromPage)
            ->setCurrentStep($step)->setTotalSteps(self::STEPS)
            ->setRouteBaseParams(['reportId'=>$reportId, 'transactionId' => $transactionId]);


        // create (add mode) or load transaction (edit mode)
        if ($transactionId) {
            $transaction = $this->getRestClient()->get('report/transaction/' . $transactionId, 'Report\\Account');
        } else {
            $transaction = new EntityDir\Report\Transaction();
        }

        // add URL-data into model
        isset($dataFromUrl['category']) && $transaction->setCategory($dataFromUrl['category']);
        isset($dataFromUrl['type']) && $transaction->setType($dataFromUrl['type']);
        //TODO fix going forward in step keping params
        $stepRedirector->setStepUrlAdditionalParams([
            'data' => $dataFromUrl
        ]);

        // crete and handle form
        $form = $this->createForm(new FormDir\Report\MoneyTransactionType($step), $transaction);
        $form->handleRequest($request);

        if ($form->get('save')->isClicked() && $form->isValid()) {
            // decide what data in the partial form needs to be passed to next step
            if ($step == 1) {
                $stepUrlData['category'] = $transaction->getCategory();
            }

            if ($step == 2) {
                $stepUrlData['type'] = $transaction->getType();
            }

            // last step: save
            if ($step == self::STEPS) {
                if ($transactionId) {
                    //TODO
//                    $this->getRestClient()->put('/transaction/' . $transactionId, $transaction, ['transaction']);
                    //back to summary
                } else {
                    //TODO
                    //$this->getRestClient()->post('report/' . $reportId . '/transaction', $transaction, ['transaction']);
                    return $this->redirectToRoute('money_in_add_another', ['reportId' => $reportId]);
                }
            }

            $stepRedirector->setStepUrlAdditionalParams([
                'data' => $stepUrlData
            ]);

            return $this->redirect($stepRedirector->getRedirectLinkAfterSaving());
        }

        return [
            'transaction' => $transaction,
            'report' => $report,
            'step' => $step,
            'reportStatus' => new ReportStatusService($report),
            'form' => $form->createView(),
            'backLink' => $stepRedirector->getBackLink(),
            'skipLink' => null,
        ];
    }

    /**
     * @Route("/report/{reportId}/money-in/add_another", name="money_in_add_another")
     * @Template()
     */
    public function addAnotherAction(Request $request, $reportId)
    {
        $report = $this->getReportIfReportNotSubmitted($reportId);

        $form = $this->createForm(new FormDir\Report\MoneyTransactionAddAnotherType(), $report);
        $form->handleRequest($request);

        if ($form->isValid()) {
            switch ($form['addAnother']->getData()) {
                case 'yes':
                    return $this->redirectToRoute('money_in_step', ['reportId' => $reportId, 'step' => 1]);
                case 'no':
                    return $this->redirectToRoute('money_in_summary', ['reportId' => $reportId]);
            }
        }

        return [
            'form' => $form->createView(),
            'report' => $report,
        ];
    }

    /**
     * @Route("/report/{reportId}/money-in/summary", name="money_in_summary")
     *
     * @param int $reportId
     * @Template()
     *
     * @return array
     */
    public function summaryAction($reportId)
    {
        $report = $this->getReportIfReportNotSubmitted($reportId, ['TODO']);
        if (!$report->hasMoneyIn()) {
            //TODO enable when save is implemented
            //return $this->redirectToRoute('money_in', ['reportId' => $reportId]);
        }

        return [
            'report' => $report,
        ];
    }

    /**
     * @Route("/report/{reportId}/money-in/{transactionId}/delete", name="bank_account_delete")
     *
     * @param int $reportId
     * @param int $transactionId
     *
     * @return RedirectResponse
     */
    public function deleteAction(Request $request, $reportId, $transactionId)
    {

//        $report = $this->getReportIfReportNotSubmitted($reportId, ['account']);
//
//        $request->getSession()->getFlashBag()->add(
//            'notice',
//            'Bank account deleted'
//        );
//
//        if ($report->hasAccountWithId($transactionId)) {
//            $this->getRestClient()->delete("/account/{$transactionId}");
//        }
//
//        return $this->redirect($this->generateUrl('money_in_summary', ['reportId' => $reportId]));
    }
}
