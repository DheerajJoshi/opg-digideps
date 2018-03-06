<?php

namespace AppBundle\Entity\Report;

use AppBundle\Entity\Client;
use AppBundle\Entity\Report\VisitsCare;
use AppBundle\Entity\Report\Lifestyle;
use AppBundle\Entity\Report\ProfServiceFee;
use AppBundle\Entity\Report\Traits as ReportTraits;
use AppBundle\Entity\ReportInterface;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ExecutionContextInterface;

/**
 * @Assert\Callback(methods={"isValidEndDate", "isValidDateRange"})
 * @Assert\Callback(methods={"debtsValid"}, groups={"debts"})
 * @Assert\Callback(methods={"feesValid"}, groups={"fees"})
 * @Assert\Callback(methods={"unsubmittedSectionAtLeastOnce"}, groups={"unsubmitted_sections"})
 */
class Report implements ReportInterface
{
    use ReportTraits\ReportAssetTrait;
    use ReportTraits\ReportBalanceTrait;
    use ReportTraits\ReportBankAccountsTrait;
    use ReportTraits\ReportDebtsTrait;
    use ReportTraits\ReportDeputyExpenseTrait;
    use ReportTraits\ReportGiftTrait;
    use ReportTraits\ReportMoneyShortTrait;
    use ReportTraits\ReportMoneyTransactionTrait;
    use ReportTraits\ReportMoreInfoTrait;
    use ReportTraits\ReportPaFeeExpensesTrait;
    use ReportTraits\ReportProfServiceFeesTrait;
    use ReportTraits\ReportUnsubmittedSections;


    const TYPE_103 = '103';
    const TYPE_102 = '102';
    const TYPE_104 = '104';
    const TYPE_103_4 = '103-4';
    const TYPE_102_4 = '102-4';

    const TYPE_103_6 = '103-6';
    const TYPE_102_6 = '102-6';
    const TYPE_104_6 = '104-6';
    const TYPE_103_4_6 = '104-4-6';
    const TYPE_102_4_6 = '102-4-6';

    const TYPE_103_5 = '103-5';
    const TYPE_102_5 = '102-5';
    const TYPE_104_5 = '104-5';
    const TYPE_103_4_5 = '103-4-5';
    const TYPE_102_4_5 = '102-4-5';

    /**
     * @JMS\Type("integer")
     * @JMS\Groups({"visits-care"})
     *
     * @var int
     */
    private $id;

    /**
     * @JMS\Type("string")
     *
     * see TYPE_* constant
     *
     * @var string
     */
    private $type;


    /**
     * @JMS\Type("boolean")
     *
     * @var bool
     */
    private $has106flag;

    /**
     * @JMS\Type("DateTime<'Y-m-d'>")
     * @JMS\Groups({"startEndDates"})
     *
     * @Assert\NotBlank( message="report.startDate.notBlank")
     * @Assert\Date( message="report.startDate.invalidMessage" )
     *
     * @var \DateTime
     */
    private $startDate;

    /**
     * @JMS\Type("DateTime<'Y-m-d'>")
     * @JMS\Groups({"startEndDates"})
     *
     * @Assert\NotBlank( message="report.endDate.notBlank" )
     * @Assert\Date( message="report.endDate.invalidMessage" )
     *
     * @var \DateTime
     */
    private $endDate;

    /**
     * @JMS\Type("DateTime<'Y-m-d'>")
     * @JMS\Groups({"report_due_date"})
     *
     * @var \DateTime
     */
    private $dueDate;

    /**
     * @var \DateTime
     *
     * @JMS\Type("DateTime")
     * @JMS\Groups({"submit"})
     */
    private $submitDate;


    /**
     * @var \DateTime
     *
     * @JMS\Type("DateTime<'Y-m-d'>")
     * @JMS\Groups({"unsubmit_date"})
     */
    private $unSubmitDate;

    /**
     * @JMS\Type("AppBundle\Entity\User")
     *
     * @var User
     */
    private $submittedBy;

    /**
     * @JMS\Type("AppBundle\Entity\Client")
     *
     * @var Client
     */
    private $client;

    /**
     * @JMS\Exclude
     *
     * @var string
     */
    private $period;

    /**
     * @JMS\Type("array<AppBundle\Entity\Report\MoneyTransfer>")
     *
     * @var MoneyTransfer[]
     */
    private $moneyTransfers;

    /**
     * @JMS\Type("array<AppBundle\Entity\Report\Contact>")
     *
     * @var Contact[]
     */
    private $contacts;

    /**
     * @JMS\Type("array<AppBundle\Entity\Report\Decision>")
     *
     * @var Decision[]
     */
    private $decisions;

    /**
     * @JMS\Type("AppBundle\Entity\Report\VisitsCare")
     *
     * @var VisitsCare
     */
    private $visitsCare;

    /**
     * @JMS\Type("AppBundle\Entity\Report\Lifestyle")
     *
     * @var Lifestyle
     */
    private $lifestyle;

    /**
     * @JMS\Type("AppBundle\Entity\Report\Action")
     *
     * @var Action
     */
    private $action;

    /**
     * @JMS\Type("AppBundle\Entity\Report\MentalCapacity")
     *
     * @var MentalCapacity
     */
    private $mentalCapacity;

    /**
     * @JMS\Type("string")
     * @JMS\Groups({"reasonForNoContacts"})
     *
     * @Assert\NotBlank( message="contact.reasonForNoContacts.notBlank", groups={"reasonForNoContacts"})
     *
     * @var string
     */
    private $reasonForNoContacts;

    /**
     * @JMS\Type("string")
     * @JMS\Groups({"reasonForNoDecisions"})
     *
     * @Assert\NotBlank( message="decision.reasonForNoDecisions.notBlank", groups={"reason-no-decisions"})
     *
     * @var string
     */
    private $reasonForNoDecisions;

    /**
     * @JMS\Type("boolean")
     * @JMS\Groups({"noAssetsToAdd"})
     *
     * @var bool
     */
    private $noAssetToAdd;

    /**
     * @JMS\Type("boolean")
     * @JMS\Groups({"money-transfers-no-transfers"})
     *
     * @var bool
     */
    private $noTransfersToAdd;

    /**
     * @JMS\Type("boolean")
     * @JMS\Groups({"submit", "submitted"})
     *
     * @var bool
     */
    private $submitted;

    /**
     * @JMS\Type("boolean")
     *
     * @var bool
     */
    private $reportSeen;

    /**
     * @var bool
     * @JMS\Type("boolean")
     *
     * @Assert\True(message="report.agree", groups={"declare"} )
     */
    private $agree;

    /**
     * @var string
     *
     * @JMS\Type("string")
     * @JMS\Groups({"report","submit", "submit_agreed"})
     *
     * @Assert\NotBlank(message="report.agreedBehalfDeputy.notBlank", groups={"declare"} )
     */
    private $agreedBehalfDeputy;

    /**
     * @var string
     *
     * @JMS\Type("string")
     * @JMS\Groups({"report","submit", "submit_agreed"})
     *
     * @Assert\NotBlank(message="report.agreedBehalfDeputyExplanation.notBlank", groups={"declare-explanation"} )
     */
    private $agreedBehalfDeputyExplanation;

    /**
     * @var Document[]
     *
     * @JMS\Groups({"report-documents"})
     * @JMS\Type("array<AppBundle\Entity\Report\Document>")
     */
    private $documents;

    /**
     * @JMS\Type("array<AppBundle\Entity\Report\Document>")
     * @JMS\Groups({"report-documents"})
     *
     * @var Document[]
     */
    private $submittedDocuments;

    /**
     * @JMS\Type("array<AppBundle\Entity\Report\Document>")
     * @JMS\Groups({"report-documents"})
     *
     * @var Document[]
     */
    private $unsubmittedDocuments;

    /**
     * @JMS\Type("AppBundle\Entity\Report\Status")
     *
     * @var Status
     */
    private $status;

    /**
     * @JMS\Type("string")
     * @JMS\Groups({"report", "wish-to-provide-documentation", "report-documents"})
     *
     * @Assert\NotBlank(message="document.wishToProvideDocumentation.notBlank", groups={"wish-to-provide-documentation"})
     */
    private $wishToProvideDocumentation;

    /**
     * @var array
     *
     * @JMS\Type("array")
     */
    private $availableSections;


    /**
     * @var string yes/no
     *
     * @JMS\Type("string")
     * @JMS\Groups({"report", "current-prof-payments-received"})
     * @Assert\NotBlank(message="common.yesnochoice.notBlank", groups={"current-prof-payments-received"})
     */
    private $currentProfPaymentsReceived;

    /**
     * @var string yes/no
     *
     * @JMS\Type("string")
     * @JMS\Groups({"report", "report-prof-estimate-fees"})
     * @Assert\NotBlank(message="profServiceFee.estimates.previousProfFeesEstimateGiven.notBlank", groups={"previous-prof-fees-estimate-choice"})
     */
    private $previousProfFeesEstimateGiven;

    /**
     * @var string
     *
     * @JMS\Type("string")
     * @JMS\Groups({"report", "report-prof-estimate-fees"})
     */
    private $profFeesEstimateSccoReason;

    /**
     * @return int $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return Report
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param  string $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return bool
     */
    public function getHas106flag()
    {
        return $this->has106flag;
    }

    /**
     * @param bool $has106flag
     */
    public function setHas106flag($has106flag)
    {
        $this->has106flag = $has106flag;
    }

    /**
     * @return \DateTime
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * @param \DateTime $startDate
     *
     * @return Report
     */
    public function setStartDate(\DateTime $startDate = null)
    {
        if ($startDate instanceof \DateTime) {
            $startDate->setTime(0, 0, 0);
        }
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * @return \DateTime $endDate
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * @param \DateTime $dueDate
     */
    public function setDueDate(\DateTime $dueDate = null)
    {
        $this->dueDate = $dueDate;
    }


    /**
     * Return the date 8 weeks after the end date.
     *
     * @return \DateTime|null $dueDate
     */
    public function getDueDate()
    {
        return $this->dueDate;
    }

    /**
     * Returns the days left to the due report
     * 0 = same day
     * -1 = overdue by 1 day
     * 1 = 1 day
     *
     * @param  \DateTime|null $currentDate
     * @return int|void
     */
    public function getDueDateDiffDays(\DateTime $currentDate = null)
    {
        if (!$this->getDueDate()) {
            return;
        }

        $currentDate = $currentDate ? $currentDate : new \DateTime();

        // clone and set time to 0,0,0 (might not be needed)
        $currentDate = clone $currentDate;
        $currentDate->setTime(0, 0, 0);
        $dueDate = clone $this->getDueDate();
        $dueDate->setTime(0, 0, 0);

        $days = (int) $currentDate->diff($dueDate)->format('%R%a');

        return $days;
    }

    /**
     * Get submitDate.
     *
     * @return \DateTime
     */
    public function getSubmitDate()
    {
        return $this->submitDate;
    }

    /**
     * @param \DateTime $submitDate
     *
     * @return Report
     */
    public function setSubmitDate(\DateTime $submitDate = null)
    {
        $this->submitDate = $submitDate;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUnSubmitDate()
    {
        return $this->unSubmitDate;
    }

    /**
     * @param \DateTime $unSubmitDate
     *
     * @return Report
     */
    public function setUnSubmitDate(\DateTime $unSubmitDate)
    {
        $this->unSubmitDate = $unSubmitDate;

        return $this;
    }

    /**
     * @return User
     */
    public function getSubmittedBy()
    {
        return $this->submittedBy;
    }

    /**
     * @param \DateTime $endDate
     *
     * @return Report
     */
    public function setEndDate(\DateTime $endDate = null)
    {
        if ($endDate instanceof \DateTime) {
            $endDate->setTime(23, 59, 59);
        }
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * Return string representation of the start-end date period
     * e.g. 2004 to 2005.
     *
     * @return string $period
     */
    public function getPeriod()
    {
        if ($this->period) {
            return $this->period;
        }

        if (!$this->startDate instanceof \DateTime || !$this->endDate instanceof \DateTime) {
            return $this->period;
        }

        $startDateStr = $this->startDate->format('Y');
        $endDateStr = $this->endDate->format('Y');

        if ($startDateStr != $endDateStr) {
            $this->period = $startDateStr . ' to ' . $endDateStr;

            return $this->period;
        }
        $this->period = $startDateStr;

        return $this->period;
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param int $client
     *
     * @return Report
     */
    public function setClient(Client $client)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * @return MoneyTransfer[]
     */
    public function getMoneyTransfers()
    {
        return $this->moneyTransfers;
    }

    /**
     * @return MoneyTransfer
     */
    public function getMoneyTransferWithId($id)
    {
        foreach ($this->moneyTransfers as $t) {
            if ($t->getId() == $id) {
                return $t;
            }
        }

        return;
    }

    /**
     * @param  array $transfers
     * @return $this
     */
    public function setMoneyTransfers(array $transfers)
    {
        $this->moneyTransfers = $transfers;

        return $this;
    }

    /**
     * @return array $contacts
     */
    public function getContacts()
    {
        return $this->contacts;
    }

    /**
     * @param array $contacts
     *
     * @return array $contacts
     */
    public function setContacts($contacts)
    {
        $this->contacts = $contacts;

        return $this;
    }

    /**
     * @var array
     */
    public function getDecisions()
    {
        return $this->decisions;
    }

    /**
     * @param Decision[] $decisions
     *
     * @return Report
     */
    public function setDecisions($decisions)
    {
        $this->decisions = $decisions;

        return $this;
    }

    /**
     * @param ExecutionContextInterface $context
     */
    public function isValidEndDate(ExecutionContextInterface $context)
    {
        if ($this->startDate > $this->endDate) {
            $context->addViolationAt('endDate', 'report.endDate.beforeStart');
        }
    }

    /**
     * @param ExecutionContextInterface $context
     *
     * @return type
     */
    public function isValidDateRange(ExecutionContextInterface $context)
    {
        if (!empty($this->endDate) && !empty($this->startDate)) {
            $dateInterval = $this->startDate->diff($this->endDate);
        } else {
            $context->addViolationAt('endDate', 'report.endDate.invalidMessage');

            return;
        }

        if ($dateInterval->days > 366) {
            $context->addViolationAt('endDate', 'report.endDate.greaterThan12Months');
        }
    }

    /**
     * Return true when the report is Due (today's date => report end date).
     *
     * @return bool
     * @Assert\True(message="report.submissionExceptions.due", groups={"due"})
     */
    public function isDue()
    {
        if (!$this->getEndDate() instanceof \DateTime) {
            return false;
        }

        // reset time on dates
        $today = new \DateTime();
        $today->setTime(0, 0, 0);

        $reportDueOn = clone $this->getEndDate();
        $reportDueOn->setTime(0, 0, 0);

        return $today >= $reportDueOn;
    }

    public function hasContacts()
    {
        if (empty($this->getContacts()) && $this->getReasonForNoContacts() === null) {
            return null;
        }

        return $this->getReasonForNoContacts() ? 'no' : 'yes';
    }

    public function setHasContacts($value)
    {
        // necessary to simplify form logic
        return null;
    }

    /**
     * @return string yes/no/null
     */
    public function hasDecisions()
    {
        if (empty($this->getDecisions()) && !$this->getReasonForNoDecisions()) {
            return null;
        }

        return $this->getReasonForNoDecisions() ? 'no' : 'yes';
    }

    public function setHasDecisions($value)
    {
        // necessary to simplify form logic
        return null;
    }

    /**
     * @param string $reasonForNoContacts
     *
     * @return Report
     */
    public function setReasonForNoContacts($reasonForNoContacts)
    {
        $this->reasonForNoContacts = $reasonForNoContacts;

        return $this;
    }

    /**
     * @return string $reasonForNoContacts
     */
    public function getReasonForNoContacts()
    {
        return $this->reasonForNoContacts;
    }

    /**
     * @param string $reasonForNoDecisions
     *
     * @return Report
     */
    public function setReasonForNoDecisions($reasonForNoDecisions)
    {
        $this->reasonForNoDecisions = $reasonForNoDecisions;

        return $this;
    }

    /**
     * @return string $reasonForNoDecisions
     */
    public function getReasonForNoDecisions()
    {
        return $this->reasonForNoDecisions;
    }

    /**
     * @return VisitsCare
     */
    public function getVisitsCare()
    {
        return $this->visitsCare ?: new VisitsCare();
    }

    /**
     * @param VisitsCare $visitsCare
     */
    public function setVisitsCare($visitsCare)
    {
        $this->visitsCare = $visitsCare;
    }

    /**
     * @return Lifestyle
     */
    public function getLifestyle()
    {
        return $this->lifestyle ?: new Lifestyle();
    }

    /**
     * @param Lifestyle $lifestyle
     */
    public function setLifestyle($lifestyle)
    {
        $this->lifestyle = $lifestyle;
    }

    public function getAction()
    {
        return $this->action ?: new Action();
    }

    /**
     * @param Action $action
     *
     * @return Report
     */
    public function setAction(Action $action)
    {
        $this->action = $action;

        return $this;
    }

    /**
     * @return MentalCapacity
     */
    public function getMentalCapacity()
    {
        return $this->mentalCapacity;
    }

    /**
     * @param MentalCapacity $mentalCapacity
     */
    public function setMentalCapacity(MentalCapacity $mentalCapacity)
    {
        $this->mentalCapacity = $mentalCapacity;

        return $this;
    }

    /**
     * @return bool $noAssetToAdd
     */
    public function getNoAssetToAdd()
    {
        return $this->noAssetToAdd;
    }

    /**
     * @param bool $noAssetToAdd
     *
     * @return Report
     */
    public function setNoAssetToAdd($noAssetToAdd)
    {
        $this->noAssetToAdd = $noAssetToAdd;

        return $this;
    }

    /**
     * @return bool
     */
    public function getNoTransfersToAdd()
    {
        return $this->noTransfersToAdd;
    }

    /**
     * @param  bool $noTransfersToAdd
     * @return $this
     */
    public function setNoTransfersToAdd($noTransfersToAdd)
    {
        $this->noTransfersToAdd = $noTransfersToAdd;

        return $this;
    }

    /**
     * @return bool $submitted
     */
    public function getSubmitted()
    {
        return $this->submitted;
    }

    /**
     * @param bool $submitted
     *
     * @return Report
     */
    public function setSubmitted($submitted)
    {
        $this->submitted = $submitted;

        return $this;
    }

    /**
     * @param bool $reportSeen
     *
     * @return Report
     */
    public function setReportSeen($reportSeen)
    {
        $this->reportSeen = $reportSeen;
    }

    /**
     * @return bool
     */
    public function getReportSeen()
    {
        return $this->reportSeen;
    }

    /**
     * @return bool
     */
    public function isAgree()
    {
        return $this->agree;
    }

    /**
     * @param bool $agree
     */
    public function setAgree($agree)
    {
        $this->agree = $agree;
    }

    /**
     * @return string
     */
    public function getAgreedBehalfDeputy()
    {
        return $this->agreedBehalfDeputy;
    }

    /**
     * @param string $agreedBehalfDeputy
     */
    public function setAgreedBehalfDeputy($agreedBehalfDeputy)
    {
        $this->agreedBehalfDeputy = $agreedBehalfDeputy;

        return $this;
    }

    /**
     * @return string
     */
    public function getAgreedBehalfDeputyExplanation()
    {
        return $this->agreedBehalfDeputyExplanation;
    }

    /**
     * @param string $agreedBehalfDeputyExplanation
     */
    public function setAgreedBehalfDeputyExplanation($agreedBehalfDeputyExplanation)
    {
        $this->agreedBehalfDeputyExplanation = $agreedBehalfDeputyExplanation;

        return $this;
    }

    /**
     * @return Document[]
     */
    public function getDocuments()
    {
        return $this->documents;
    }

    /**
     * @return Document[]
     */
    public function getSubmittedDocuments()
    {
        return $this->submittedDocuments;
    }

    /**
     * @return Document[]
     */
    public function getUnsubmittedDocuments()
    {
        return $this->unsubmittedDocuments;
    }

    /**
     * @return Document[]
     */
    public function getDocumentsExcludingReportPdf()
    {
        return array_filter($this->documents, function ($document) {
            /* @var $document Document */
            return !$document->isReportPdf();
        });
    }

    /**
     * @param Document[] $documents
     */
    public function setDocuments($documents)
    {
        $this->documents = $documents;
    }

    /**
     * @return Status
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param Status $status $statusrvice
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return mixed
     */
    public function getWishToProvideDocumentation()
    {
        return $this->wishToProvideDocumentation;
    }

    /**
     * @param $wishToProvideDocumentation
     * @return $this
     */
    public function setWishToProvideDocumentation($wishToProvideDocumentation)
    {
        $this->wishToProvideDocumentation = $wishToProvideDocumentation;

        return $this;
    }

    /**
     * @param $format string where %s are endDate (Y), submitDate Y-m-d, case number
     * @return string
     */
    public function createAttachmentName($format)
    {
        $attachmentName = sprintf($format,
            $this->getEndDate()->format('Y'),
            $this->getSubmitDate() ? $this->getSubmitDate()->format('Y-m-d') : 'n-a-', //some old reports have no submission date
            $this->getClient()->getCaseNumber()
        );

        return $attachmentName;
    }

    /**
     * @return string
     */
    public function getZipName()
    {
        $client = $this->getClient();

        return 'Report_' . $client->getCaseNumber()
            . '_' . $this->getStartDate()->format('Y')
            . '_' . $this->getEndDate()->format('Y')
            . '.zip';
    }

    /**
     * @return array
     */
    public function getAvailableSections()
    {
        return $this->availableSections;
    }

    /**
     * @param  array $availableSections
     * @return Report
     */
    public function setAvailableSections($availableSections)
    {
        $this->availableSections = $availableSections;

        return $this;
    }

    /**
     * @param  string $section
     * @return bool
     */
    public function hasSection($section)
    {
        return in_array($section, $this->getAvailableSections());
    }

    /**
     * Has this report been submitted?
     *
     * @return bool
     */
    public function isSubmitted()
    {
        return (bool)$this->getSubmitted();
    }

    /**
     * Generates the translation suffic to use depending on report type,
     *
     * 10x followed by "-104" for HW, "-4" for hybrid report and nothing for PF report
     *
     * @return string
     */
    public function get104TransSuffix()
    {
        return (strpos($this->getType(), '-4') > 0) ?
            '-4' :
            ($this->getType() === '104' || $this->getType() === '104-6' ?
                '-104' : ''
            );
    }

    public function shouldShowBalanceWarning()
    {
        // if not due dont show warning
        if (!$this->isDue()) {
            return false;
        }

        // if accounts not started don't show warning
        if ($this->getStatus()->getBankAccountsState()['state'] == Status::STATE_NOT_STARTED) {
            return false;
        }

        switch ($this->getType()) {
            case Report::TYPE_102:
            case Report::TYPE_102_4:
                // if a money section not started, dont show warning
                if ($this->getStatus()->getMoneyInState()['state'] == Status::STATE_NOT_STARTED ||
                    $this->getStatus()->getMoneyOutState()['state'] == Status::STATE_NOT_STARTED
                ) {
                    return false;
                }
                break;
            default:
                return false;
        }

        return true;
    }

    /**
     * @return string
     */
    public function getCurrentProfPaymentsReceived()
    {
        return $this->currentProfPaymentsReceived;
    }

    /**
     * @param $currentProfPaymentsReceived
     *
     * @return $this
     */
    public function setCurrentProfPaymentsReceived($currentProfPaymentsReceived)
    {
        $this->currentProfPaymentsReceived = $currentProfPaymentsReceived;

        return $this;
    }


    /**
     * Return filtered array of ProfServiceFee's
     *
     * @param string $feeTypeId current|estimated|previous
     * @param string $fixedOrAssessed
     * @return array
     * @throws \Exception
     */
     public function getFilteredFees($feeTypeId, $fixedOrAssessed)
    {
        switch ($feeTypeId) {
            case ProfServiceFee::TYPE_CURRENT_FEE:
                $fees = $this->getProfServiceFeesByType(ProfServiceFee::TYPE_CURRENT_FEE);
                break;
            case ProfServiceFee::TYPE_ESTIMATED_FEE:
                $fees = $this->getProfServiceFeesByType(ProfServiceFee::TYPE_ESTIMATED_FEE);
                break;
            case ProfServiceFee::TYPE_PREVIOUS_FEE:
                $fees = $this->getProfServiceFeesByType(ProfServiceFee::TYPE_PREVIOUS_FEE);
                break;
            default:
                throw new \Exception('Invalid Fee type Id:' . $feeTypeId);
        }

        return array_filter($fees, function ($profServiceFee) use ($fixedOrAssessed) {
            /** @var $profServiceFee ProfServiceFee  */
            return $profServiceFee->getAssessedOrFixed() === $fixedOrAssessed;
        });
    }

    /**
     * @param string $feeTypeId "current"|"estimated"|"previous"
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getProfServiceFeesByType($feeTypeId)
    {
        if (!in_array(
            $feeTypeId,
            [
                ProfServiceFee::TYPE_CURRENT_FEE,
                ProfServiceFee::TYPE_PREVIOUS_FEE,
                ProfServiceFee::TYPE_ESTIMATED_FEE
            ]
        )) {
            throw new \Exception('Invalid feeTypeId: ' . $feeTypeId);
        }

        return array_filter($this->getProfServiceFees(), function ($profServiceFee) use ($feeTypeId) {
            /** @var $profServiceFee \AppBundle\Entity\Report\ProfServiceFee */
            return $profServiceFee->getFeeTypeId() === $feeTypeId;
        });
    }


    /**
     * @return string
     */
    public function getPreviousProfFeesEstimateGiven()
    {
        return $this->previousProfFeesEstimateGiven;
    }

    /**
     * @param string $previousProfFeesEstimateGiven
     * @return $this
     */
    public function setPreviousProfFeesEstimateGiven($previousProfFeesEstimateGiven)
    {
        $this->previousProfFeesEstimateGiven = $previousProfFeesEstimateGiven;
        return $this;
    }

    /**
     * @return string
     */
    public function getProfFeesEstimateSccoReason()
    {
        return $this->profFeesEstimateSccoReason;
    }

    /**
     * @param string $profFeesEstimateSccoReason
     * @return $this
     */
    public function setProfFeesEstimateSccoReason($profFeesEstimateSccoReason)
    {
        $this->profFeesEstimateSccoReason = $profFeesEstimateSccoReason;
        return $this;
    }
}
