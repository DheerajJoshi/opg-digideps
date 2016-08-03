<?php

namespace AppBundle\Entity\Odr;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Doctrine\Common\Collections\ArrayCollection;
use AppBundle\Entity\Client;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Entity\Odr\OdrRepository")
 * @ORM\Table(name="odr")
 */
class Odr
{
    const PROPERTY_AND_AFFAIRS = 2;

    private static $stateBenefitsKeys = [
        'employment_support_allowance_incapacity_benefit',
        'income_support_pension_guarantee_credit',
        'income_related_employment_and_support_allowance',
        '',
        // ...11 in total. copy later //TODO
    ];

    /**
     * @var int
     *
     * @JMS\Groups({"odr", "odr_id"})
     * @JMS\Type("integer")
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\SequenceGenerator(sequenceName="odr_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var Client
     *
     * @JMS\Groups({"client"})
     * @ORM\OneToOne(targetEntity="AppBundle\Entity\Client", inversedBy="odr")
     * @ORM\JoinColumn(name="client_id", referencedColumnName="id")
     */
    private $client;

    /**
     * @var VisitsCare
     *
     * @JMS\Groups({"odr"})
     * @JMS\Type("AppBundle\Entity\Odr\VisitsCare")
     * @ORM\OneToOne(targetEntity="AppBundle\Entity\Odr\VisitsCare", mappedBy="odr", cascade={"persist"})
     **/
    private $visitsCare;

    /**
     * @var Account[]
     *
     * @JMS\Groups({"odr-account"})
     * @JMS\Type("array<AppBundle\Entity\Odr\Account>")
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Odr\Account", mappedBy="odr", cascade={"persist"})
     */
    private $bankAccounts;

    /**
     * @var Debt[]
     *
     * @JMS\Groups({"odr-debt"})
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Odr\Debt", mappedBy="odr", cascade={"persist"})
     * @ORM\OrderBy({"id" = "ASC"})
     */
    private $debts;

    /**
     * @var IncomeOneOff[]
     *
     * @JMS\Groups({"odr-income-one-off"})
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Odr\IncomeOneOff", mappedBy="odr", cascade={"persist"})
     * @ORM\OrderBy({"id" = "ASC"})
     */
    private $incomeOneOff;

    /**
     * @var bool
     *
     * @JMS\Type("string")
     * @JMS\Groups({"odr-debt"})
     *
     * @ORM\Column(name="has_debts", type="string", length=5, nullable=true)
     *
     * @var string
     */
    private $hasDebts;

    /**
     * @var Asset[]
     *
     * @JMS\Groups({"odr-asset"})
     * @JMS\Type("array<AppBundle\Entity\Odr\Asset>")
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Odr\Asset", mappedBy="odr", cascade={"persist"})
     */
    private $assets;

    /**
     * @var bool
     * @JMS\Type("boolean")
     * @JMS\Groups({"basic"})
     * @ORM\Column(name="no_asset_to_add", type="boolean", options={ "default": false}, nullable=true)
     */
    private $noAssetToAdd;

    /**
     * @var array
     *
     * @JMS\Groups({"odr-income-state-benefits"})
     * @JMS\Type("array")
     * @ORM\Column(name="state_benefits", type="array", nullable=true)
     */
    private $stateBenefits;

    /**
     * @var string
     *
     * @JMS\Type("string")
     * @JMS\Groups({"odr-income-pension"})
     * @ORM\Column(name="receive_state_pension", type="text", nullable=true)
     */
    private $receiveStatePension;

    /**
     * @var string
     *
     * @JMS\Type("string")
     * @JMS\Groups({"odr-income-pension"})
     * @ORM\Column(name="receive_other_income", type="text", nullable=true)
     */
    private $receiveOtherIncome;

    /**
     * @var string
     *
     * @JMS\Type("string")
     * @JMS\Groups({"odr-income-damages"})
     * @ORM\Column(name="expect_compensation", type="text", nullable=true)
     */
    private $expectCompensation;

    /**
     * @var bool
     *
     * @JMS\Groups({"odr"})
     * @JMS\Type("boolean")
     * @ORM\Column(name="submitted", type="boolean", nullable=true)
     */
    private $submitted;

    /**
     * @var \DateTime
     *
     * @JMS\Groups({"odr"})
     * @JMS\Accessor(getter="getSubmitDate")
     * @JMS\Type("DateTime")
     * @ORM\Column(name="submit_date", type="datetime", nullable=true)
     */
    private $submitDate;

    /**
     * Odr constructor.
     *
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->bankAccounts = new ArrayCollection();
        $this->debts = new ArrayCollection();
        $this->assets = new ArrayCollection();
        $this->incomeOneOff = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param int $client
     */
    public function setClient(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @return mixed
     */
    public function getVisitsCare()
    {
        return $this->visitsCare;
    }

    /**
     * @param mixed $visitsCare
     */
    public function setVisitsCare($visitsCare)
    {
        $this->visitsCare = $visitsCare;
    }

    /**
     * @return bool
     */
    public function getSubmitted()
    {
        return $this->submitted;
    }

    /**
     * @param bool $submitted
     */
    public function setSubmitted($submitted)
    {
        $this->submitted = $submitted;
    }

    /**
     * @return \DateTime
     */
    public function getSubmitDate()
    {
        return $this->submitDate;
    }

    /**
     * @param \DateTime $submitDate
     */
    public function setSubmitDate($submitDate)
    {
        $this->submitDate = $submitDate;
    }

    /**
     * @return mixed
     */
    public function getBankAccounts()
    {
        return $this->bankAccounts;
    }

    /**
     * @param mixed $bankAccounts
     */
    public function setBankAccounts($bankAccounts)
    {
        $this->bankAccounts = $bankAccounts;
    }

    /**
     * @return mixed
     */
    public function getDebts()
    {
        return $this->debts;
    }

    /**
     * @param mixed $debts
     */
    public function setDebts($debts)
    {
        $this->debts = $debts;
    }

    /**
     * @return mixed
     */
    public function getHasDebts()
    {
        return $this->hasDebts;
    }

    /**
     * @param mixed $hasDebts
     */
    public function setHasDebts($hasDebts)
    {
        $this->hasDebts = $hasDebts;
    }

    /**
     * @param Debt $debt
     */
    public function addDebt(Debt $debt)
    {
        if (!$this->debts->contains($debt)) {
            $this->debts->add($debt);
        }

        return $this;
    }

    /**
     * @param string $typeId
     *
     * @return Debt
     */
    public function getDebtByTypeId($typeId)
    {
        return $this->getDebts()->filter(function (Debt $debt) use ($typeId) {
            return $debt->getDebtTypeId() == $typeId;
        })->first();
    }

    /**
     * Get assets total value.
     *
     * @JMS\VirtualProperty
     * @JMS\Type("string")
     * @JMS\SerializedName("debts_total_amount")
     * @JMS\Groups({"odr-debt"})
     *
     * @return float
     */
    public function getDebtsTotalAmount()
    {
        $ret = 0;
        foreach ($this->getDebts() as $debt) {
            $ret += $debt->getAmount();
        }

        return $ret;
    }

    /**
     * Add assets.
     *
     * @param Asset $assets
     *
     * @return Odr
     */
    public function addAsset(Asset $assets)
    {
        $this->assets[] = $assets;

        return $this;
    }

    /**
     * Remove assets.
     *
     * @param Asset $assets
     */
    public function removeAsset(Asset $assets)
    {
        $this->assets->removeElement($assets);
    }

    /**
     * Get assets.
     *
     * @return Asset[]
     */
    public function getAssets()
    {
        return $this->assets;
    }

    /**
     * Set noAssetToAdd.
     *
     * @param bool $noAssetToAdd
     *
     * @return Odr
     */
    public function setNoAssetToAdd($noAssetToAdd)
    {
        $this->noAssetToAdd = $noAssetToAdd;

        return $this;
    }

    /**
     * Get noAssetToAdd.
     *
     * @return bool
     */
    public function getNoAssetToAdd()
    {
        return $this->noAssetToAdd;
    }

    /**
     * @return IncomeOneOff[]
     */
    public function getIncomeOneOff()
    {
        return $this->incomeOneOff;
    }

    /**
     * @param IncomeOneOff[] $incomeOneOff
     * @return Odr
     */
    public function setIncomeOneOff($incomeOneOff)
    {
        $this->incomeOneOff = $incomeOneOff;
        return $this;
    }

    /**
     * @return array
     */
    public function getStateBenefits()
    {
        return $this->stateBenefits;
    }

    /**
     * @param array $stateBenefits
     * @return Odr
     */
    public function setStateBenefits($stateBenefits)
    {
        $this->stateBenefits = $stateBenefits;
        return $this;
    }

    /**
     * @return string
     */
    public function getReceiveStatePension()
    {
        return $this->receiveStatePension;
    }

    /**
     * @param string $receiveStatePension
     * @return Odr
     */
    public function setReceiveStatePension($receiveStatePension)
    {
        $this->receiveStatePension = $receiveStatePension;
        return $this;
    }

    /**
     * @return string
     */
    public function getReceiveOtherIncome()
    {
        return $this->receiveOtherIncome;
    }

    /**
     * @param string $receiveOtherIncome
     * @return Odr
     */
    public function setReceiveOtherIncome($receiveOtherIncome)
    {
        $this->receiveOtherIncome = $receiveOtherIncome;
        return $this;
    }

    /**
     * @return string
     */
    public function getExpectCompensation()
    {
        return $this->expectCompensation;
    }

    /**
     * @param string $expectCompensation
     * @return Odr
     */
    public function setExpectCompensation($expectCompensation)
    {
        $this->expectCompensation = $expectCompensation;
        return $this;
    }


    /**
     * @param string $typeId
     *
     * @return IncomeOneOff
     */
    public function getIncomeOneOffByTypeId($typeId)
    {
        return $this->getIncomeOneOff()->filter(function (IncomeOneOff $incomeOneOff) use ($typeId) {
            return $incomeOneOff->getTypeId() == $typeId;
        })->first();
    }


}
