<?php
namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity as EntityDir;
use AppBundle\Exception as AppExceptions;

class AccountController extends RestController
{
    
    /**
     * @Route("/report/get-accounts/{id}")
     * @Method({"GET"})
     */
    public function getAccountsAction(Request $request, $id)
    {
        if ($request->query->has('groups')) {
            $this->setJmsSerialiserGroups($request->query->get('groups'));
        }
        
        $report = $this->findEntityBy('Report', $id);
      
        $accounts = $this->getRepository('Account')->findByReport($report, [
            'id' => 'DESC'
        ]);
       
        if(count($accounts) == 0){
            return [];
        }
        return $accounts;
    }
    
    /**
     * @Route("/report/add-account")
     * @Method({"POST"})
     */
    public function addAccountAction()
    {
        $data = $this->deserializeBodyContent([
           'bank' => 'notEmpty', 
           'sort_code' => 'notEmpty', 
           'opening_date' => 'notEmpty', 
           'opening_balance' => 'notEmpty'
        ]);
        
        $report = $this->findEntityBy('Report', $data['report']);
        if (empty($report)) {
            throw new AppExceptions\NotFound("Report id: " . $data['report'] . " does not exists");
        }
        
        $account = new EntityDir\Account();
        $account->setReport($report);
        
        $this->fillAccountData($account, $data);
        
        $this->getRepository('Account')->addEmptyTransactionsToAccount($account);
        
        $this->getEntityManager()->persist($account);
        $this->getEntityManager()->flush();
        
        return [ 'id' => $account->getId() ];
    }
    
   /**
     * @Route("/report/find-account-by-id/{id}")
     * @Method({"GET"})
     */
    public function getOneById(Request $request, $id)
    {
        if ($request->query->has('groups')) {
            $this->setJmsSerialiserGroups($request->query->get('groups'));
        }
        
        $account = $this->findEntityBy('Account', $id, 'Account not found');

        return $account;
    }
    
    /**
     * @Route("/account/{id}")
     * @Method({"PUT"})
     */
    public function edit($id)
    {
        $account = $this->findEntityBy('Account', $id, 'Account not found'); /* @var $account EntityDir\Account*/ 
        
        $data = $this->deserializeBodyContent();
        
        $this->fillAccountData($account, $data);
        
        // edit transactions
        if (isset($data['money_in']) && isset($data['money_out'])) {
            $transactionRepo = $this->getRepository('AccountTransaction');
            array_map(function($transactionRow) use ($transactionRepo) {
                $transactionRepo->find($transactionRow['id'])
                    ->setAmount($transactionRow['amount'])
                    ->setMoreDetails($transactionRow['more_details']);
            }, array_merge($data['money_in'], $data['money_out']));
            $this->setJmsSerialiserGroups('transactions');
        }
        
        
        $account->setLastEdit(new \DateTime());
        
        $this->getEntityManager()->flush();
        
        return $account;
    }
    
    /**
     * @Route("/account/{id}")
     * @Method({"DELETE"})
     */
    public function accountDelete($id)
    {
        $account = $this->findEntityBy('Account', $id, 'Account not found'); /* @var $account EntityDir\Account */

        foreach ($account->getTransactions() as $transaction) {
            $this->getEntityManager()->remove($transaction);
        }
        
        $this->getEntityManager()->remove($account);
        
        $this->getEntityManager()->flush();
        
        return [];
    }
    
    private function fillAccountData(EntityDir\Account $account, array $data)
    {
         //basicdata
        if (array_key_exists('bank', $data)) {
           $account->setBank($data['bank']);
        }
        
        if (array_key_exists('sort_code', $data)) {
           $account->setSortCode($data['sort_code']);
        }
        
        if (array_key_exists('account_number', $data)) {
           $account->setAccountNumber($data['account_number']);
        }
        
        if (array_key_exists('opening_date', $data)) {
           $account->setOpeningDate(new \DateTime($data['opening_date']));
        }
        
        if (array_key_exists('opening_balance', $data)) {
           $account->setOpeningBalance($data['opening_balance']);
        }
        
        if (array_key_exists('opening_date_explanation', $data)) {
           $account->setOpeningDateExplanation($data['opening_date_explanation']);
        }
        
        if (array_key_exists('closing_date', $data)) {
           $account->setClosingDate(new \DateTime($data['closing_date']));
        }
        
        if (array_key_exists('closing_date_explanation', $data)) {
           $account->setClosingDateExplanation($data['closing_date_explanation']);
        }
        
        if (array_key_exists('closing_balance', $data)) {
           $account->setClosingBalance($data['closing_balance']);
        }
        
        if (array_key_exists('closing_balance_explanation', $data)) {
           $account->setClosingBalanceExplanation($data['closing_balance_explanation']);
        }
    }
    
}