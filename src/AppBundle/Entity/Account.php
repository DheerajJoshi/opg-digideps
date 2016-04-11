<?php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Annotation as JMS;

use Doctrine\ORM\QueryBuilder;

/**
 * Account
 *
 * @ORM\Table(name="account")
 * @ORM\Entity()
 */
class Account 
{
    /**
     * Keep in sync with client
     * @JMS\Exclude 
     */
    public static $types = [
        'current' => 'Current account',
        'savings' => 'Savings account',
        'isa' => 'ISA',
        'postoffice' => 'Post office account',
        'cfo' => 'Court funds office account',
        'other' => 'Other'
    ];
     
    /**
     * @var integer
     * @JMS\Groups({"transactions", "basic", "transfers"})
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\SequenceGenerator(sequenceName="account_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;
    
    
    /**
     * @var string
     * @JMS\Groups({"transactions", "basic", "transfers"})
     * @ORM\Column(name="bank_name", type="string", length=300, nullable=true)
     */
    private $bank;

    /**
     * @var string
     * @JMS\Groups({"transactions", "basic", "transfers"})
     *
     * @ORM\Column(name="account_type", type="string", length=125, nullable=true)
     */
    private $accountType;

    /**
     * @var string
     * @JMS\Groups({"transactions", "basic", "transfers"})
     * 
     * @ORM\Column(name="sort_code", type="string", length=6, nullable=true)
     */
    private $sortCode;

    /**
     * @var string
     * @JMS\Groups({"transactions", "basic", "transfers"})
     * 
     * @ORM\Column(name="account_number", type="string", length=4, nullable=true)
     */
    private $accountNumber;

    /**
     * @var \DateTime
     * @JMS\Groups({"transactions", "basic", "transfers"})
     * 
     * @ORM\Column(name="last_edit", type="datetime", nullable=true)
     */
    private $lastEdit;
    
    /**
     * @var \DateTime
     * @JMS\Groups({"transactions", "basic", "transfers"})
     * 
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     */
    private $createdAt;

    /**
     * @var decimal
     * @JMS\Groups({"transactions", "basic", "transfers"})
     * @JMS\Type("string")
     * 
     * @ORM\Column(name="opening_balance", type="decimal", precision=14, scale=2, nullable=true)
     */
    private $openingBalance;
    
    /**
     * @deprecated since accounts_mk2
     * @var string
     * @JMS\Groups({"transactions", "basic", "transfers"})
     * 
     * @ORM\Column(name="opening_date_explanation", type="text", nullable=true)
     */
    private $openingDateExplanation;

    /**
     * @var decimal
     * 
     * @JMS\Type("string")
     * @JMS\Groups({"transactions", "basic", "transfers"})
     * 
     * @ORM\Column(name="closing_balance", type="decimal", precision=14, scale=2, nullable=true)
     */
    private $closingBalance;

    /**
     * @deprecated since accounts_mk2
     * @var string
     * @JMS\Groups({"transactions", "basic", "transfers"})
     * 
     * @ORM\Column(name="closing_balance_explanation", type="text", nullable=true)
     */
    private $closingBalanceExplanation;
    
    /**
     * @deprecated since accounts_mk2
     * @var \Date
     * @JMS\Groups({"transactions", "basic", "transfers"})
     * 
     * @ORM\Column(name="opening_date", type="date", nullable=true)
     */
    private $openingDate;

    /**
     * @deprecated since accounts_mk2
     * @var \Date
     * @JMS\Groups({"transactions", "basic", "transfers"})
     * 
     * @ORM\Column(name="closing_date", type="date", nullable=true)
     */
    private $closingDate;

    /**
     * @deprecated since accounts_mk2
     * @var string
     * @JMS\Groups({"transactions", "basic", "transfers"})
     * 
     * @ORM\Column(name="closing_date_explanation", type="text", nullable=true)
     */
    private $closingDateExplanation;
    
    /**
     * @var Report
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Report", inversedBy="accounts")
     * @ORM\JoinColumn(name="report_id", referencedColumnName="id")
     */
    private $report;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->transactions = new ArrayCollection();
        $this->lastEdit = null;
        $this->createdAt = new \DateTime();
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
     * Set bank
     *
     * @param string $bank
     * @return Account
     */
    public function setBank($bank)
    {
        $this->bank = $bank;

        return $this;
    }

    /**
     * Get bank
     *
     * @return string 
     */
    public function getBank()
    {
        return $this->bank;
    }

    /**
     * @return string
     */
    public function getAccountType()
    {
        return $this->accountType;
    }

    /**
     * @return string
     */
    public function getAccountTypeText()
    {
        return isset(self::$types[$this->getAccountType()]) 
               ? self::$types[$this->getAccountType()] : null;
    }
    
    /**
     * @param string $accountType
     */
    public function setAccountType($accountType)
    {
        $this->accountType = $accountType;
    }


    /**
     * Set sortCode
     *
     * @param string $sortCode
     * @return Account
     */
    public function setSortCode($sortCode)
    {
        $this->sortCode = $sortCode;

        return $this;
    }

    /**
     * Get sortCode
     *
     * @return string 
     */
    public function getSortCode()
    {
        return $this->sortCode;
    }

    /**
     * Set accountNumber
     *
     * @param string $accountNumber
     * @return Account
     */
    public function setAccountNumber($accountNumber)
    {
        $this->accountNumber = $accountNumber;

        return $this;
    }

    /**
     * Get accountNumber
     *
     * @return string 
     */
    public function getAccountNumber()
    {
        return $this->accountNumber;
    }

    /**
     * Set lastEdit
     *
     * @param \DateTime $lastEdit
     * @return Account
     */
    public function setLastEdit($lastEdit)
    {
        $this->lastEdit = $lastEdit;

        return $this;
    }

    /**
     * Get lastEdit
     *
     * @return \DateTime 
     */
    public function getLastEdit()
    {
        return $this->lastEdit;
    }
    
    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     */
    public function setCreatedAt(\DateTime $createdAt)
    {
        $this->createdAt = $createdAt;
        return $this;
    }
    
    /**
     * Set openingBalance
     *
     * @param string $openingBalance
     * @return Account
     */
    public function setOpeningBalance($openingBalance)
    {
        $this->openingBalance = $openingBalance;

        return $this;
    }

    /**
     * Get openingBalance
     *
     * @return string 
     */
    public function getOpeningBalance()
    {
        return $this->openingBalance;
    }
    
    /**
     * @return string
     */
    public function getOpeningDateExplanation()
    {
        return $this->openingDateExplanation;
    }

    
    /**
     * @param string $openingDateExplanation
     */
    public function setOpeningDateExplanation($openingDateExplanation)
    {
        $this->openingDateExplanation = $openingDateExplanation;
        return $this;
    }

    
    /**
     * Set closingBalance
     *
     * @param string $closingBalance
     * @return Account
     */
    public function setClosingBalance($closingBalance)
    {
        $this->closingBalance = $closingBalance;

        return $this;
    }

    /**
     * Get closingBalance
     *
     * @return string 
     */
    public function getClosingBalance()
    {
        return $this->closingBalance;
    }
    
    /**
     * @return string
     */
    public function getClosingBalanceExplanation()
    {
        return $this->closingBalanceExplanation;
    }

    /**
     * @param string $closingBalanceExplanation
     */
    public function setClosingBalanceExplanation($closingBalanceExplanation)
    {
        $this->closingBalanceExplanation = $closingBalanceExplanation;
        return $this;
    }

    
    /**
     * Set openingDate
     *
     * @param \DateTime $openingDate
     * @return Account
     */
    public function setOpeningDate($openingDate)
    {
        $this->openingDate = $openingDate;

        return $this;
    }

    /**
     * Get openingDate
     *
     * @return \DateTime 
     */
    public function getOpeningDate()
    {
        return $this->openingDate;
    }

    /**
     * Set closingDate
     *
     * @param \DateTime $closingDate
     * @return Account
     */
    public function setClosingDate($closingDate)
    {
        $this->closingDate = $closingDate;

        return $this;
    }

    /**
     * Get closingDate
     *
     * @return \DateTime 
     */
    public function getClosingDate()
    {
        return $this->closingDate;
    }
    
    /**
     * @return string
     */
    public function getClosingDateExplanation()
    {
        return $this->closingDateExplanation;
    }

    /**
     * @param string $closingDateExplanation
     */
    public function setClosingDateExplanation($closingDateExplanation)
    {
        $this->closingDateExplanation = $closingDateExplanation;
        return $this;
    }

    /**
     * Set report
     *
     * @param Report $report
     * @return Account
     */
    public function setReport(Report $report = null)
    {
        $this->report = $report;

        return $this;
    }

    /**
     * Get report
     *
     * @return Report 
     */
    public function getReport()
    {
        return $this->report;
    }
    
     
    /**
     * Sort code required
     * @return string
     */
    public function requiresBankNameAndSortCode()
    {
        return !in_array($this->getAccountType(), ['postoffice', 'cfo']);
    }

}
