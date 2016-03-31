<?php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Doctrine\Common\Collections\ArrayCollection;

use Doctrine\ORM\QueryBuilder;

/**
 * Reports
 * @JMS\XmlRoot("report")
 * @JMS\ExclusionPolicy("NONE")
 * @ORM\Table(name="report")
 * @ORM\Entity(repositoryClass="AppBundle\Entity\ReportRepository")
 * @ORM\Entity(repositoryClass="AppBundle\Entity\ReportRepository")
 */
class Report 
{
    const PROPERTY_AND_AFFAIRS = 2;
    
    /**
     * @var integer
     *
     * @JMS\Groups({"basic"})
     * @JMS\Type("integer")
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\SequenceGenerator(sequenceName="report_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var integer
     * 
     * @JMS\Groups({"basic"})
     * @JMS\Type("AppBundle\Entity\Client")
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Client", inversedBy="reports")
     * @ORM\JoinColumn(name="client_id", referencedColumnName="id")
     */
    private $client;
    
    /**
     * @JMS\Groups({"contacts"})
     * @JMS\Type("array<AppBundle\Entity\Contact>")
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Contact", mappedBy="report", cascade={"persist"})
     */
    private $contacts;
    
    /**
     * @JMS\Groups({"accounts"})
     * @JMS\Type("array<AppBundle\Entity\Account>")
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Account", mappedBy="report", cascade={"persist"})
     */
    private $accounts;

    /**
     * @JMS\Groups({"transfers"})
     * @JMS\Type("array<AppBundle\Entity\MoneyTransfer>")
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\MoneyTransfer", mappedBy="report", cascade={"persist"})
     */
    private $moneyTransfers;
    
    
    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Transaction", mappedBy="report", cascade={"persist"})
     * @ORM\OrderBy({"id" = "ASC"})
     */
    private $transactions;
    
    /**
     * @JMS\Groups({"decisions"})
     * @JMS\Type("array<AppBundle\Entity\Decision>")
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Decision", mappedBy="report", cascade={"persist"})
     */
    private $decisions;
    
    /**
     * @JMS\Groups({"asset"})
     * @JMS\Type("array<AppBundle\Entity\Asset>")
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Asset", mappedBy="report", cascade={"persist"})
     */
    private $assets;

    /**
     * @JMS\Groups({"basic"})
     * @JMS\Type("AppBundle\Entity\Safeguarding")
     * @ORM\OneToOne(targetEntity="AppBundle\Entity\Safeguarding",  mappedBy="report", cascade={"persist"})
     **/
    private $safeguarding;
    
    /**
     * @JMS\Groups({"basic", "action"})
     * @JMS\Type("AppBundle\Entity\Action")
     * @ORM\OneToOne(targetEntity="AppBundle\Entity\Action",  mappedBy="report", cascade={"persist"})
     **/
    private $action;

    /**
     * @JMS\Groups({ "basic"})
     * @JMS\Accessor(getter="getCourtOrderTypeId")
     * @JMS\Type("integer")
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\CourtOrderType", inversedBy="reports")
     * @ORM\JoinColumn( name="court_order_type_id", referencedColumnName="id" )
     */
    private $courtOrderType;

    /**
     * @var string
     *
     * @JMS\Groups({ "basic"})
     * @JMS\Type("string")
     * @ORM\Column(name="title", type="string", length=150, nullable=true)
     */
    private $title;

    /**
     * @var \Date
     *
     * @JMS\Groups({ "basic"})
     * @JMS\Accessor(getter="getStartDate")
     * @JMS\Type("DateTime<'Y-m-d'>")
     * @ORM\Column(name="start_date", type="date", nullable=true)
     */
    private $startDate;

    /**
     * @var \DateTime
     * 
     * @JMS\Groups({ "basic"})
     * @JMS\Accessor(getter="getEndDate")
     * @JMS\Type("DateTime<'Y-m-d'>")
     * @ORM\Column(name="end_date", type="date", nullable=true)
     */
    private $endDate;

    /**
     * @var \DateTime
     * 
     * @JMS\Groups({ "basic"})
     * @JMS\Accessor(getter="getSubmitDate")
     * @JMS\Type("DateTime")
     * @ORM\Column(name="submit_date", type="datetime", nullable=true)
     */
    private $submitDate;

    /**
     * @var \DateTime
     * @JMS\Accessor(getter="getLastedit")
     * @JMS\Type("DateTime<'Y-m-d'>")
     * @ORM\Column(name="last_edit", type="datetime", nullable=true)
     */	 
    private $lastedit;

    /**
     * @var string
     *
     * @JMS\Type("string")
     * @JMS\Groups({"basic"})
     * @ORM\Column(name="further_information", type="text", nullable=true)
     */
    private $furtherInformation;
    
    /**
     * @var boolean
     * @JMS\Type("boolean")
     * @JMS\Groups({"basic"})
     * @ORM\Column(name="no_asset_to_add", type="boolean", options={ "default": false}, nullable=true)
     */
    private $noAssetToAdd;
    
    /**
     * @var boolean
     * @JMS\Type("boolean")
     * @JMS\Groups({"basic", "transfers"})
     * @ORM\Column(name="no_transfers_to_add", type="boolean", options={ "default": false}, nullable=true)
     */
    private $noTransfersToAdd;
    
    /**
     * @var string
     *
     * @JMS\Type("string")
     * @JMS\Groups({"basic"})
     * @ORM\Column(name="reason_for_no_contacts", type="text", nullable=true)
     */
    private $reasonForNoContacts;
    
    /**
     * @var string
     *
     * @JMS\Type("string")
     * @JMS\Groups({"basic"})
     * @ORM\Column(name="reason_for_no_decisions", type="text", nullable=true)
     **/
    private $reasonForNoDecisions;

    /**
     * @var boolean
     *
     * @JMS\Groups({ "basic"})
     * @JMS\Type("boolean")
     * @ORM\Column(name="submitted", type="boolean", nullable=true)
     */
    private $submitted;
    
    /**
     * @var boolean
     * @JMS\Groups({"basic"})
     * @JMS\Type("boolean")
     * @ORM\Column(name="reviewed", type="boolean", nullable=true)
     */
    private $reviewed;
    
    /**
     * @var boolean
     * @JMS\Groups({"basic"})
     * @JMS\Type("boolean")
     * @ORM\Column(name="report_seen", type="boolean", options={"default": true})
     */
    private $reportSeen;

    /**
     * @var boolean
     *
     * @JMS\Type("boolean")
     * @JMS\Groups({"basic"})
     * @ORM\Column(name="all_agreed", type="boolean", nullable=true)
     */
    private $allAgreed;

    /** @var string
     *
     * @JMS\Type("string")
     * @JMS\Groups({"basic"})
     * @ORM\Column(name="reason_not_all_agreed", type="text", nullable=true)
     */
    private $reasonNotAllAgreed;

    /**
     * @var string
     * @JMS\Groups({"balance", "basic"})
     * @JMS\Type("string")
     *
     * @ORM\Column(name="balance_mismatch_explanation", type="text", nullable=true)
     */
    private $balanceMismatchExplanation;

     /**
     * Constructor
     */
    public function __construct()
    {
        $this->contacts = new ArrayCollection();
        $this->accounts = new ArrayCollection();
        $this->moneyTransfers = new ArrayCollection();
        $this->transactions = new ArrayCollection();
        $this->decisions = new ArrayCollection();
        $this->assets = new ArrayCollection();
        $this->noAssetToAdd = null;
        $this->noTransfersToAdd = null;
        $this->reportSeen = true;
    }
    
    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return Report
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string 
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set startDate
     *
     * @param \DateTime $startDate
     * @return Report
     */
    public function setStartDate(\DateTime $startDate)
    {
        $this->startDate = new \DateTime($startDate->format('Y-m-d'));
        
        return $this;
    }

    /**
     * Get startDate
     *
     * @return \DateTime 
     */
    public function getStartDate()
    {   
        return $this->startDate;
    }

    /**
     * Set endDate
     *
     * @param \DateTime $endDate
     * @return Report
     */
    public function setEndDate(\DateTime $endDate)
    {
        $this->endDate = new \DateTime($endDate->format('Y-m-d'));

        return $this;
    }

    /**
     * Get endDate
     *
     * @return \DateTime 
     */
    public function getEndDate()
    {   
        return $this->endDate;
    }

    /**
     * Set submitDate
     *
     * @param string $submitDate
     * @return Report
     */
    public function setSubmitDate(\DateTime $submitDate = null)
    {
        $this->submitDate = $submitDate;

        return $this;
    }

    /**
     * Get submitDate
     *
     * @return \DateTime 
     */
    public function getSubmitDate()
    {
        return $this->submitDate;
    }

    /**
     * Set lastedit
     *
     * @param \DateTime $lastedit
     * @return Report
     */
    public function setLastedit(\DateTime $lastedit)
    {
        $this->lastedit = new \DateTime($lastedit->format('Y-m-d'));

        return $this;
    }

    /**
     * Get lastedit
     *
     * @return \DateTime 
     */
    public function getLastedit()
    {
        return $this->lastedit;
    }

    /**
     * Set furtherInformation
     *
     * @param string $furtherInformation
     * @return Report
     */
    public function setFurtherInformation($furtherInformation)
    {
        $furtherInformation = trim($furtherInformation, " \n");
        
        $this->furtherInformation = $furtherInformation;

        return $this;
    }

    /**
     * Get furtherInformation
     *
     * @return string 
     */
    public function getFurtherInformation()
    {
        return $this->furtherInformation;
    }

    /**
     * Set submitted
     *
     * @param boolean $submitted
     * @return Report
     */
    public function setSubmitted($submitted)
    {
        $this->submitted = $submitted;

        return $this;
    }

    /**
     * Get submitted
     *
     * @return boolean 
     */
    public function getSubmitted()
    {
        return $this->submitted;
    }
    
    /**
     * @return boolean
     */
    public function getReviewed()
    {
        return $this->reviewed;
    }

    /**
     * @param boolean $reviewed
     */
    public function setReviewed($reviewed)
    {
        $this->reviewed = $reviewed;
        return $this;
    }

    /**
     * Set client
     *
     * @param Client $client
     * @return Report
     */
    public function setClient(Client $client = null)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Get client
     *
     * @return Client 
     */
    public function getClient()
    {
        return $this->client;
    }
    
    public function getClientId()
    {
        return $this->client->getId();
    }

    /**
     * Add contacts
     *
     * @param Contact $contacts
     * @return Report
     */
    public function addContact(Contact $contacts)
    {
        $this->contacts[] = $contacts;

        return $this;
    }
    /**
     * Remove contacts
     *
     * @param Contact $contacts
     */
    public function removeContact(Contact $contacts)
    {
        $this->contacts->removeElement($contacts);
    }

    /**
     * Get contacts
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getContacts()
    {
        return $this->contacts;
    }

    /**
     * Add accounts
     *
     * @param Account $accounts
     * @return Report
     */
    public function addAccount(Account $accounts)
    {
        $this->accounts[] = $accounts;

        return $this;
    }

    /**
     * Remove accounts
     *
     * @param Account $accounts
     */
    public function removeAccount(Account $accounts)
    {
        $this->accounts->removeElement($accounts);
    }

    /**
     * Get accounts
     *
     * @return Account[]
     */
    public function getAccounts()
    {
        return $this->accounts;
    }
    
    /**
     * @return MoneyTransfer[]
     */
    public function getMoneyTransfers()
    {
        return $this->moneyTransfers;
    }

    /**
     * @param MoneyTransfer $moneyTransfer
     * @return \Report
     */
    public function addMoneyTransfers(MoneyTransfer $moneyTransfer)
    {
        $this->moneyTransfers->add($moneyTransfer);
        
        return $this;
    }

    
    /**
     * Add decisions
     *
     * @param Decision $decision
     * @return Report
     */
    public function addDecision(Decision $decision)
    {
        $this->decisions[] = $decision;

        return $this;
    }

    /**
     * Remove decisions
     *
     * @param Decision $decision
     */
    public function removeDecision(Decision $decision)
    {
        $this->decisions->removeElement($decision);
    }

    /**
     * Get decisions
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getDecisions()
    {
        return $this->decisions;
    }

    /**
     * Add assets
     *
     * @param Asset $assets
     * @return Report
     */
    public function addAsset(Asset $assets)
    {
        $this->assets[] = $assets;

        return $this;
    }

    /**
     * Remove assets
     *
     * @param Asset $assets
     */
    public function removeAsset(Asset $assets)
    {
        $this->assets->removeElement($assets);
    }

    /**
     * Get assets
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getAssets()
    {
        return $this->assets;
    }
    
    
    /**
     * Get array of assets grouped by type
     *
     * @return array of Asset[]
     */
    public function getAssetsGroupedByType()
    {
        $ret = array();
        
        foreach ($this->getAssets() as $asset) {
            $type = $asset->getType();
            if ($type == 'other') {
                $type = $asset->getTitle();
            }
            if (isset($ret[$type])) {
                $ret[$type][] = $asset;
            } else {
                $ret[$type] = array($asset);
            }
        }
    
        // sort the assets by their type now.
        ksort($ret);
    
        return $ret;
    }
    
    /**
     * Get assets total value
     *
     * @return float
     */
    public function getAssetsTotalValue()
    {
        $ret = 0;
        foreach ($this->getAssets() as $asset) {
            $ret += $asset->getValue();
        }
        
        return $ret;
    }
    
    /**
     * Set noAssetToAdd
     *
     * @param boolean $noAssetToAdd
     * @return Report
     */
    public function setNoAssetToAdd($noAssetToAdd)
    {
        $this->noAssetToAdd = $noAssetToAdd;

        return $this;
    }

    /**
     * Get noAssetToAdd
     *
     * @return boolean 
     */
    public function getNoAssetToAdd()
    {
        return $this->noAssetToAdd;
    }
    
    /**
     * 
     * @return boolean
     */
    public function getNoTransfersToAdd()
    {
        return $this->noTransfersToAdd;
    }

    /**
     * @param boolean $noTransfersToAdd
     */
    public function setNoTransfersToAdd($noTransfersToAdd)
    {
        $this->noTransfersToAdd = $noTransfersToAdd;
        return $this;
    }
    

    /**
     * Get Safeguarding
     *
     * @return Safeguarding
     */
    public function getSafeguarding()
    {
        return $this->safeguarding;
    }

    /**
     * Set Safeguarding
     *
     * @param Safeguarding $safeguarding
     * @return Report
     */
    public function setSafeguarding(Safeguarding $safeguarding = null)
    {
        $this->safeguarding = $safeguarding;

        return $this;
    }

    /**
     * @return Action
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * 
     * @param \AppBundle\Entity\Action $action
     * @return \AppBundle\Entity\Report
     */
    public function setAction(Action $action)
    {
        $this->action = $action;
        
        return $this;
    }

        
    /**
     * Set reasonForNoContact
     *
     * @param string $reasonForNoContacts
     * @return Report
     */
    public function setReasonForNoContacts($reasonForNoContacts)
    {
        $this->reasonForNoContacts = $reasonForNoContacts;

        return $this;
    }

    /**
     * Get reasonForNoContacts
     *
     * @return string 
     */
    public function getReasonForNoContacts()
    {
        return $this->reasonForNoContacts;
    }

    /**
     * Set reasonForNoDecisions
     *
     * @param string $reasonForNoDecisions
     * @return Report
     **/
    public function setReasonForNoDecisions($reasonForNoDecisions)
    {
        $this->reasonForNoDecisions = $reasonForNoDecisions;

        return $this;
    }

    /**
     * Get ReasonForNoDecisions
     *
     * @return string
     */
    public function getReasonForNoDecisions()
    {
        return $this->reasonForNoDecisions;
    }
    

    /**
     * Set courtOrderType
     *
     * @param CourtOrderType $courtOrderType
     * @return Report
     */
    public function setCourtOrderType(CourtOrderType $courtOrderType = null)
    {
        $this->courtOrderType = $courtOrderType;

        return $this;
    }

    /**
     * Get courtOrderType
     *
     * @return CourtOrderType 
     */
    public function getCourtOrderType()
    {
        return $this->courtOrderType;
    }
    
    public function getCourtOrderTypeId()
    {
        return $this->courtOrderType->getId();
    }

    /**
     * Set reportSeen
     *
     * @param boolean $reportSeen
     * @return Report
     */
    public function setReportSeen($reportSeen)
    {
        $this->reportSeen = $reportSeen;

        return $this;
    }

    /**
     * Get reportSeen
     *
     * @return boolean 
     */
    public function getReportSeen()
    {
        return $this->reportSeen;
    }
    
    /**
     * @param User $user
     * 
     * @return boolean
     */
    public function belongsToUser(User $user)
    {
        return in_array($user->getId(), $this->getClient()->getUserIds());
    }

    /**
     * @return boolean
     */
    public function isAllAgreed()
    {
        return $this->allAgreed;
    }

    /**
     * @param boolean $allAgreed
     */
    public function setAllAgreed($allAgreed)
    {
        $this->allAgreed = $allAgreed;
    }


    /**
     * @return string
     */
    public function getReasonNotAllAgreed()
    {
        return $this->reasonNotAllAgreed;
    }

    /**
     * @param string $reasonNotAllAgreed
     */
    public function setReasonNotAllAgreed($reasonNotAllAgreed)
    {
        $this->reasonNotAllAgreed = $reasonNotAllAgreed;
    }

    /**
     * @return string
     */
    public function getBalanceMismatchExplanation()
    {
        return $this->balanceMismatchExplanation;
    }

    /**
     * @param string $balanceMismatchExplanation
     */
    public function setBalanceMismatchExplanation($balanceMismatchExplanation)
    {
        $this->balanceMismatchExplanation = $balanceMismatchExplanation;
    }



    /**
     * @return Transaction[]
     */
    public function getTransactions()
    {
        return $this->transactions;
    }

    /**
     * Virtual JMS property with IN transaction
     *
     * @JMS\VirtualProperty
     * @JMS\Groups({"transactionsIn"})
     * @JMS\Type("array<AppBundle\Entity\Transaction>")
     * @JMS\SerializedName("transactions_in")
     *
     * @return Transaction[]
     */
    public function getTransactionsIn()
    {
        $ret = [];
        
        foreach ($this->transactions as $t) {
            if ($t->getTransactionType() instanceof TransactionTypeIn) {
                $ret[] = $t;
            }
        }
        uasort($ret, function ($t1, $t2) {
            return $t1->getTransactionType()->getDisplayOrder() >= $t2->getTransactionType()->getDisplayOrder();
        });
        
        return $ret;
    }

    /**
     * Virtual JMS property with OUT transaction
     *
     * @JMS\VirtualProperty
     * @JMS\Groups({"transactionsOut"})
     * @JMS\Type("array<AppBundle\Entity\Transaction>")
     * @JMS\SerializedName("transactions_out")
     *
     * @return Transaction[]
     */
    public function getTransactionsOut()
    {
        $ret = [];
        
        foreach ($this->transactions as $t) {
            if ($t->getTransactionType() instanceof TransactionTypeOut) {
                $ret[] = $t;
            }
        }
        uasort($ret, function ($t1, $t2) {
            return $t1->getTransactionType()->getDisplayOrder() >= $t2->getTransactionType()->getDisplayOrder();
        });
        
        return $ret;
    }



    /**
     * @param Transaction $transaction
     */
    public function addTransaction(Transaction $transaction)
    {
        if (!$this->transactions->contains($transaction)) {
            $this->transactions->add($transaction);
        }

        return $this;
    }


    /**
     * @param string $transactionTypeId
     *
     * @return Transaction
     */
    public function getTransactionByTypeId($transactionTypeId)
    {
        return $this->getTransactions()->filter(function(Transaction $transaction) use($transactionTypeId) {
            return $transaction->getTransactionTypeId() == $transactionTypeId;
        })->first();
    }

    /**
     * @JMS\VirtualProperty
     * @JMS\Groups({"balance"})
     * @JMS\Type("double")
     * @JMS\SerializedName("money_in_total")
     */
    public function getMoneyInTotal()
    {
        $ret = 0;
        foreach ($this->getTransactionsIn() as $t) {
            $ret += $t->getAmountsTotal();
        }

        return $ret;
    }

    /**
     * @JMS\VirtualProperty
     * @JMS\Groups({"balance"})
     * @JMS\Type("double")
     * @JMS\SerializedName("money_out_total")
     */
    public function getMoneyOutTotal()
    {
        $ret = 0;
        foreach ($this->getTransactionsOut() as $t) {
            $ret +=  $t->getAmountsTotal();
        }

        return $ret;
    }

    /**
     * @JMS\VirtualProperty
     * @JMS\Groups({"balance"})
     * @JMS\Type("double")
     * @JMS\SerializedName("accounts_opening_balance_total")
     */
    public function getAccountsOpeningBalanceTotal()
    {
        $ret = 0;
        foreach ($this->getAccounts() as $a) {
            $ret += $a->getOpeningBalance();
        }

        return $ret;
    }

    /**
     * Return sum of closing balances (if all of them have a value, otherwise returns null)
     * 
     * @JMS\VirtualProperty
     * @JMS\Groups({"balance"})
     * @JMS\Type("double")
     * @JMS\SerializedName("accounts_closing_balance_total")
     * 
     * @return float 
     */
    public function getAccountsClosingBalanceTotal()
    {
        $ret = 0;
        foreach ($this->getAccounts() as $a) {
            if ($a->getClosingBalance() === null) {
                return null;
            }
            $ret += $a->getClosingBalance();
        }

        return $ret;
    }

    /**
     * @JMS\VirtualProperty
     * @JMS\Groups({"balance"})
     * @JMS\Type("double")
     * @JMS\SerializedName("calculated_balance")
     */
    public function getCalculatedBalance()
    {
        return $this->getAccountsOpeningBalanceTotal()
        + $this->getMoneyInTotal()
        - $this->getMoneyOutTotal();
    }

    /**
     * @JMS\VirtualProperty
     * @JMS\Groups({"balance"})
     * @JMS\Type("double")
     * @JMS\SerializedName("totals_offset")
     */
    public function getTotalsOffset()
    {
        return $this->getCalculatedBalance() - $this->getAccountsClosingBalanceTotal();
    }

    /**
     * @JMS\VirtualProperty
     * @JMS\Groups({"balance", "basic"})
     * @JMS\Type("boolean")
     * @JMS\SerializedName("totals_match")
     */
    public function getTotalsMatch()
    {
        return abs($this->getTotalsOffset()) < 0.2;
    }

    /**
     * @param Transaction[] $transactions
     *
     * @return array array of [category=>[entries=>[[id=>,type=>]], amountTotal[]]]
     */
    public function groupByCategory($transactions)
    {
        $ret = [];

        foreach ($transactions as $id => $t) {
            $cat = $t->getCategoryString();
            if (!isset($ret[$cat])) {
                $ret[$cat] = ['entries'=>[], 'amountTotal'=>0];
            }
            $ret[$cat]['entries'][$id] = $t; // needed to find the corresponding transaction in the form
            $ret[$cat]['amountTotal'] += $t->getAmountsTotal();
        }

        return $ret;
    }

    public function isDue()
    {
        if (!$this->getEndDate() instanceof \DateTime) {
            return false;
        }

        // reset time on dates
        $today = new \DateTime;
        $today->setTime(0, 0, 0);

        $reportDueOn = clone $this->getEndDate();
        $reportDueOn->setTime(0, 0, 0);

        return $today >= $reportDueOn;
    }
    
}
