<?php

namespace AppBundle\Entity\Odr;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;

/**
 * @ORM\Entity
 * @ORM\Table(name="odr_income_one_off")
 */
class OneOff
{
    public static $oneOffKeys = [
        'bequest_or_inheritance' => false,
        'cash_gift_received'     => false,
        'refunds'                => false,
        'sale_of_an_asset'       => false,
        'sale_of_investment'     => false,
        'sale_of_property'       => false,
    ];

    /**
     * @var int
     *
     * @JMS\Type("integer")
     * @JMS\Groups({"one-off"})
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\SequenceGenerator(sequenceName="odr_oneoff_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var Odr
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Odr\Odr", inversedBy="oneOff")
     * @ORM\JoinColumn(name="odr_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $odr;

    /**
     * @var string
     * @JMS\Groups({"one-off"})
     * @ORM\Column(name="type_id", type="string", nullable=false)
     */
    private $typeId;

    /**
     * @var string
     *
     * @JMS\Type("boolean")
     * @JMS\Groups({"one-off"})
     * @ORM\Column(name="present", type="boolean", nullable=true)
     */
    private $present;

    /**
     * @var string
     *
     * @JMS\Groups({"one-off"})
     * @ORM\Column(name="has_more_details", type="string", nullable=false)
     */
    private $hasMoreDetails;

    /**
     * @var string
     * @JMS\Groups({"one-off"})
     * @ORM\Column(name="more_details", type="string", nullable=true)
     */
    private $moreDetails;

    /**
     * @param Odr    $odr
     * @param string $typeId
     * @param float  $amount
     */
    public function __construct(Odr $odr, $typeId, $hasMoreDetails)
    {
        $this->odr = $odr;
        $this->typeId = $typeId;
        $this->present = null;
        $this->hasMoreDetails = $hasMoreDetails;
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
     * @return Odr
     */
    public function getOdr()
    {
        return $this->odr;
    }

    /**
     * @param Odr $odr
     */
    public function setOdr($odr)
    {
        $this->odr = $odr;
    }

    /**
     * @return string
     */
    public function getTypeId()
    {
        return $this->typeId;
    }

    /**
     * @param string $typeId
     *
     * @return IncomeOneOff
     */
    public function setTypeId($typeId)
    {
        $this->typeId = $typeId;

        return $this;
    }

    /**
     * @return string
     */
    public function getPresent()
    {
        return $this->present;
    }

    /**
     * @param string $present
     *
     * @return IncomeBenefit
     */
    public function setPresent($present)
    {
        $this->present = $present;

        return $this;
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

        return $this;
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

        return $this;
    }
}
