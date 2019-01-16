<?php

namespace AppBundle\Entity\Report;

use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

class ProfDeputyEstimateCost
{
    /**
     * @JMS\Type("string")
     * @JMS\Groups({"prof-deputy-estimate-costs"})
     */
    private $profDeputyOtherCostTypeId;

    /**
     * @var decimal
     *
     * @JMS\Type("string")
     * @JMS\Groups({"prof-deputy-estimate-costs"})
     * @Assert\Type(type="numeric", message="profDeputyOtherCost.amount.notNumeric", groups={"prof-deputy-estimate-costs"})
     * @Assert\Range(min=0, max=100000000, minMessage = "profDeputyOtherCost.amount.minMessage", maxMessage = "profDeputyOtherCost.amount.maxMessage", groups={"prof-deputy-estimate-costs"})
     */
    private $amount;

    /**
     * @var string
     * @JMS\Groups({"prof-deputy-estimate-costs"})
     * @JMS\Type("boolean")
     */
    private $hasMoreDetails;

    /**
     * @var string
     * @JMS\Groups({"prof-deputy-estimate-costs"})
     * @JMS\Type("string")
     *
     * @Assert\NotBlank(message="profDeputyOtherCost.moreDetails.notEmpty", groups={"prof-deputy-estimate-cost-more-details"})
     */
    private $moreDetails;

    /**
     * ProfDeputyEstimateCost constructor.
     *
     * @param $profDeputyEstimateCostTypeId
     * @param decimal $amount
     * @param string  $hasMoreDetails
     * @param string  $moreDetails
     */
    public function __construct($profDeputyEstimateCostTypeId, $amount, $hasMoreDetails, $moreDetails)
    {
        $this->profDeputyEstimateCostTypeId = $profDeputyEstimateCostTypeId;
        $this->amount = $amount;
        $this->hasMoreDetails = $hasMoreDetails;
        $this->moreDetails = $moreDetails;
    }

    /**
     * @return mixed
     */
    public function getProfDeputyEstimateCostTypeId()
    {
        return $this->profDeputyEstimateCostTypeId;
    }

    /**
     * @param $profDeputyEstimateCostTypeId
     */
    public function setProfDeputyEstimateCostTypeId($profDeputyEstimateCostTypeId)
    {
        $this->profDeputyEstimateCostTypeId = $profDeputyEstimateCostTypeId;
    }

    /**
     * @return decimal
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param decimal $amount
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
    }

    /**
     * @return string
     */
    public function getHasMoreDetails()
    {
        return $this->hasMoreDetails;
    }

    /**
     * @param string $hasMoreDetails
     */
    public function setHasMoreDetails($hasMoreDetails)
    {
        $this->hasMoreDetails = $hasMoreDetails;
    }

    /**
     * @return string
     */
    public function getMoreDetails()
    {
        return $this->moreDetails;
    }

    /**
     * @param string $moreDetails
     */
    public function setMoreDetails($moreDetails)
    {
        $this->moreDetails = $moreDetails;
    }
}
