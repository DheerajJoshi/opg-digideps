<?php

namespace AppBundle\Entity\Report;

use AppBundle\Entity as EntityDir;
use Doctrine\ORM\EntityRepository;

/**
 * ReportRepository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ReportRepository extends EntityRepository
{
    /**
     * Create new year's report copying data over (and set start/endDate accordingly).
     *
     * @param EntityDir\Report\Report $report
     *
     * @return EntityDir\Report\Report
     */
    public function createNextYearReport(EntityDir\Report\Report $report)
    {
        //lets clone the report
        $newReport = new EntityDir\Report\Report();
        $newReport->setClient($report->getClient());
        $newReportType = $this->getReportTypeBasedOnOldReport($report);
        $newReport->setType($newReportType);
        $newReport->setStartDate($report->getEndDate()->modify('+1 day'));
        $newReport->setEndDate($report->getEndDate()->modify('+12 months -1 day'));
        $newReport->setReportSeen(false);
        $newReport->setNoAssetToAdd($report->getNoAssetToAdd());

        // clone assets
        foreach ($report->getAssets() as $asset) {
            $newAsset = clone $asset;
            $newAsset->setReport($newReport);
            $this->_em->detach($newAsset);
            $this->_em->persist($newAsset);
        }

        // clone accounts
        //  opening balance = closing balance
        //  opening date = closing date
        foreach ($report->getBankAccounts() as $account) {
            $newAccount = new EntityDir\Report\BankAccount();
            $newAccount->setBank($account->getBank());
            $newAccount->setAccountType($account->getAccountType());
            $newAccount->setSortCode($account->getSortCode());
            $newAccount->setAccountNumber($account->getAccountNumber());
            $newAccount->setOpeningBalance($account->getClosingBalance());
            $newAccount->setCreatedAt(new \DateTime());
            $newAccount->setReport($newReport);

            $this->_em->persist($newAccount);
        }
        // persist
        $this->_em->persist($newReport);
        $this->_em->flush();

        return $newReport;
    }

    /**
     * add empty Debts to Report.
     * Called from doctrine listener.
     *
     * @param Report $report
     *
     * @return int changed records
     */
    public function addDebtsToReportIfMissing(Report $report)
    {
        $ret = 0;

        // skips if already added
        if (count($report->getDebts()) > 0) {
            return $ret;
        }

        foreach (Debt::$debtTypeIds as $row) {
            $debt = new Debt($report, $row[0], $row[1], null);
            $this->_em->persist($debt);
            ++$ret;
        }

        return $ret;
    }

    /**
     * Called from doctrine listener.
     *
     * @param Report $report
     *
     * @return int changed records
     */
    public function addMoneyShortCategoriesIfMissing(Report $report)
    {
        $ret = 0;

        if (count($report->getMoneyShortCategories()) > 0) {
            return $ret;
        }

        //if ($report->getType() == Report::TYPE_103) { //re-enable when behat journey for 103 is created
            $cats = MoneyShortCategory::getCategories('in') + MoneyShortCategory::getCategories('out');
        foreach ($cats as $typeId => $options) {
            $debt = new MoneyShortCategory($report, $typeId, false);
            $this->_em->persist($debt);
            ++$ret;
        }
        //}

        return $ret;
    }

    /**
     * @param Report $oldReport
     *
     * @return string
     */
    private function getReportTypeBasedOnOldReport(Report $oldReport)
    {
        // 102 and 103 is decided based on total amount of assets below the threshold
        if (in_array($oldReport->getType(), [Report::TYPE_102, Report::TYPE_103])) {
            if (Report::ENABLE_103 && $oldReport->getAssetsTotalValue() <= Report::ASSETS_TOTAL_VALUE_103_THRESHOLD) {
                return Report::TYPE_103;
            }

            return Report::TYPE_102;
        }

        return $oldReport->getType();
    }
}
