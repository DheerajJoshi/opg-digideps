<?php
namespace AppBundle\Service;

use AppBundle\Entity\Report;
use AppBundle\Entity\Account;
use Symfony\Component\Translation\TranslatorInterface;

class ReportStatusService {

    const NOTSTARTED = "not-started";
    const DONE = "done";
    const INCOMPLETE = "incomplete";
    
    /** @var Report */
    private $report;

    /** @var TranslatorInterface */
    private $translator;

    public function __construct(Report $report, TranslatorInterface $translator)
    {
        $this->report = $report;
        $this->translator = $translator;
    }

    /** @return string */
    public function getDecisionsStatus() {
        
        $decisions = $this->report->getDecisions();
        
        if (isset($decisions)) {
        
            $count = count($decisions);
            
            if ($count == 1) {
                return "1 " . $this->translator->trans('decision');
            } else if ($count > 1) {
                return "${count} " . $this->translator->trans('decisions');
            }
        
        }

        if (empty($this->report->getReasonForNoDecisions())) {
            return $this->translator->trans('notstarted');
        } else {
            return $this->translator->trans('nodecisions');
        }
        
    }

    /** @return string */
    public function getContactsStatus() {

        $contacts = $this->report->getContacts();

        if (isset($contacts)) {

            $count = count($contacts);

            if ($count == 1) {
                return "1 " . $this->translator->trans('contact');
            } else if ($count > 1) {
                return "${count} " . $this->translator->trans('contacts');
            }

        }

        if (empty($this->report->getReasonForNoContacts())) {
            return $this->translator->trans('notstarted');
        } else {
            return $this->translator->trans('nocontacts');
        }

    }

    /** @return string */
    public function getSafeguardingStatus() {
        if ($this->missingSafeguarding()) {
            return $this->translator->trans('notstarted');
        } else {
            return $this->translator->trans('finished');
        }
    }

    /** @return string */
    public function getAccountsStatus() {
        if ($this->missingAccounts()) {
            return $this->translator->trans('notstarted');
        } 
        
        $count = count($this->report->getAccounts());
        if ($count == 1) {
            return "1 " . $this->translator->trans("account");
        } else {
            return "${count} " . $this->translator->trans("accounts");
        }
    }

    /** @return string */
    public function getAssetsStatus() {
        $assets = $this->report->getAssets();

        if (isset($assets)) {

            $count = count($assets);

            if ($count == 1) {
                return "1 " . $this->translator->trans('asset');
            } else if ($count > 1) {
                return "${count} " . $this->translator->trans('assets');
            }

        }
        
        if ($this->report->getNoAssetToAdd() == true) {
            return $this->translator->trans('noassets');
        } else {
            return $this->translator->trans('notstarted');
        }
    }

    /** @return string */
    public function getDecisionsState() {
        if ($this->missingDecisions()) {
            return $this::NOTSTARTED;
        } else {
            return $this::DONE;
        }
    }

    /** @return string */
    public function getContactsState() {
        if ($this->missingContacts()) {
            return $this::NOTSTARTED;
        } else {
            return $this::DONE;
        }
    }

    /** @return string */
    public function getSafeguardingState() {
        if ($this->missingSafeguarding()) {
            return $this::NOTSTARTED;
        } else {
            return $this::DONE;
        }
    }

    /** @return string */
    public function getAccountsState() {
        if ($this->missingAccounts()) {
            return $this::NOTSTARTED;
        } else if ($this->hasOutstandingAccounts()) {
            return $this::INCOMPLETE;
        } else {
            return $this::DONE;
        }
    }

    /** @return string */
    public function getAssetsState() {
        if ($this->missingAssets()) {
            return $this::NOTSTARTED;
        } else {
            return $this::DONE;
        }    
    }

    /** @return boolean */
    public function isReadyToSubmit()
    {
        if($this->report->getCourtOrderType() == Report::PROPERTY_AND_AFFAIRS){
            if($this->hasOutstandingAccounts() || $this->missingAccounts() || $this->missingContacts() || $this->missingAssets() || $this->missingDecisions() || $this->missingSafeguarding()){
                return false;
            }
        } else {
            if($this->missingContacts() || $this->missingDecisions() || $this->missingSafeguarding()){
                return false;
            }
        }
        return true;
    }

    /** @return boolean */
    private function missingAssets()
    {
        if( $this->report->getCourtOrderType() != Report::PROPERTY_AND_AFFAIRS ){
            return false;
        }

        if(empty($this->report->getAssets()) && (!$this->report->getNoAssetToAdd())){
            return true;
        }
        return false;
    }

    /** @return boolean */
    private function missingSafeguarding()
    {
        $safeguarding = $this->report->getSafeguarding();
        
        if (!isset($safeguarding) || $safeguarding->missingSafeguardingInfo() == true) {
            return true;
        }

        return false;
    }

    /** @return boolean */
    public function hasOutstandingAccounts()
    {
        if(empty($this->report->getOutstandingAccounts())) {
            return false;
        }

        /** @var Account $account */
        foreach($this->report->getAccounts() as $account){
            if(!$account->hasClosingBalance()){
                return true;
            }
        }
        return false;
    }

    /** @return boolean */
    private function missingContacts()
    {
        if(empty($this->report->getContacts()) && empty($this->report->getReasonForNoContacts())){
            return true;
        }
        return false;
    }

    /** @return boolean */
    private function missingDecisions()
    {
        if(empty($this->report->getDecisions()) && empty($this->report->getReasonForNoDecisions())){
            return true;
        }
        return false;
    }

    /** @return boolean */
    private function missingAccounts()
    {
        if( $this->report->getCourtOrderType() != Report::PROPERTY_AND_AFFAIRS ){
            return false;
        }

        if(empty($this->report->getAccounts())){
            return true;
        }
        return false;
    }
    
}
