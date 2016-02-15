<?php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;

use Doctrine\ORM\QueryBuilder;

/**
 * @JMS\ExclusionPolicy("NONE")
 * @ORM\Table(name="concern")
 * @ORM\Entity
 */
class Concern 
{
    /**
     * @var integer
     *
     * @JMS\Type("integer")
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\SequenceGenerator(sequenceName="concern_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @ORM\OneToOne(targetEntity="AppBundle\Entity\Report", inversedBy="concern")
     * @ORM\JoinColumn(name="report_id", referencedColumnName="id")
     */
    private $report;

    /**
     * @var string
     *
     * @JMS\Type("string")
     * @JMS\Groups({"concern"})
     * @ORM\Column(name="do_you_expect_decisions", type="string", length=4, nullable=true)
     */
    private $doYouExpectFinancialDecisions;
    
    /**
     * @var string
     *
     * @JMS\Type("string")
     * @JMS\Groups({"concern"})
     * @ORM\Column(name="do_you_expect_decisions_details", type="text", nullable=true)
     */
    private $doYouExpectFinancialDecisionsDetails;

    
    /**
     * @var string
     *
     * @JMS\Type("string")
     * @JMS\Groups({"concern"})
     * @ORM\Column( name="do_you_have_concerns", type="string", length=4, nullable=true)
     */
    private $doYouHaveConcerns;
    
    /**
     * @var string
     *
     * @JMS\Type("string")
     * @JMS\Groups({"concern"})
     * @ORM\Column( name="do_you_have_concerns_details", type="text", nullable=true)
     */
    private $doYouHaveConcernsDetails;

    /**
     * @param Report $report
     */
    public function __construct(Report $report)
    {
        $this->report = $report;
        $report->setConcern($this);
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
     * Set report
     *
     * @param Report $report
     * @return Contact
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


    public function getDoYouExpectFinancialDecisions()
    {
        return $this->doYouExpectFinancialDecisions;
    }

    public function getDoYouHaveConcerns()
    {
        return $this->doYouHaveConcerns;
    }

    public function setDoYouExpectFinancialDecisions($doYouExpectFinancialDecisions)
    {
        $this->doYouExpectFinancialDecisions = $doYouExpectFinancialDecisions;
        return $this;
    }

    public function setDoYouHaveConcerns($doYouHaveConcerns)
    {
        $this->doYouHaveConcerns = $doYouHaveConcerns;
        return $this;
    }

    public function getDoYouExpectFinancialDecisionsDetails()
    {
        return $this->doYouExpectFinancialDecisionsDetails;
    }


    public function getDoYouHaveConcernsDetails()
    {
        return $this->doYouHaveConcernsDetails;
    }


    public function setDoYouExpectFinancialDecisionsDetails($doYouExpectFinancialDecisionsDetails)
    {
        $this->doYouExpectFinancialDecisionsDetails = $doYouExpectFinancialDecisionsDetails;
        return $this;
    }


    public function setDoYouHaveConcernsDetails($doYouHaveConcernsDetails)
    {
        $this->doYouHaveConcernsDetails = $doYouHaveConcernsDetails;
        return $this;
    }




}
