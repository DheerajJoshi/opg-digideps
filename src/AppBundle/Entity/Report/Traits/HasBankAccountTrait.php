<?php

namespace AppBundle\Entity\Report\Traits;

use AppBundle\Entity\Report\BankAccount;
use JMS\Serializer\Annotation as JMS;

trait HasBankAccountTrait
{
    /**
     * @var integer
     *
     * @JMS\Groups({"account"})
     * @JMS\Type("AppBundle\Entity\Report\BankAccount")
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Report\BankAccount")
     * @ORM\JoinColumn(name="bank_account_id", referencedColumnName="id", nullable=true)
     */
    private $bankAccount;

    /**
     * @return mixed
     */
    public function getBankAccount()
    {
        return $this->bankAccount;
    }

    /**
     * @param $bankAccount
     * @return $this
     */
    public function setBankAccount($bankAccount)
    {
        $this->bankAccount = $bankAccount;
        return $this;
    }
}
