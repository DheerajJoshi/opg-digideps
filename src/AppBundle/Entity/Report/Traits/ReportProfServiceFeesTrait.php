<?php

namespace AppBundle\Entity\Report\Traits;

use AppBundle\Entity\Report\Fee;
use AppBundle\Entity\Report\ProfServiceFee;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ExecutionContextInterface;

trait ReportProfServiceFeesTrait
{
    /**
     * @JMS\Type("array<AppBundle\Entity\Report\ProfServiceFee>")
     * @JMS\Groups({"prof-service-fees"})
     *
     * @var ProfServiceFee[]
     */
    private $profServiceFees = [];

    /**
     * @return ProfServiceFee[]
     */
    public function getProfServiceFees()
    {
        return $this->profServiceFees;
    }

    /**
     * @param ProfServiceFee[] $profServiceFees
     */
    public function setProfServiceFees($profServiceFees)
    {
        $this->profServiceFees = $profServiceFees;
    }
}
