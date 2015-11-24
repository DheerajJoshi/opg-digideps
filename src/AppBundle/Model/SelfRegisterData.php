<?php

namespace AppBundle\Model;

use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * SelfRegisterData
 */
class SelfRegisterData
{

    /**
     * @var string $firstname
     * @JMS\Type("string")
     * @Assert\NotBlank( message="user.firstname.notBlank" )
     * @Assert\Length(min=2, max=50, minMessage="user.firstname.minLength", maxMessage="user.firstname.maxLength" )
     *
     */
    private $firstname;

    /**
     * @var string lastname
     * @JMS\Type("string")
     * @Assert\NotBlank(message="user.lastname.notBlank" )
     * @Assert\Length(min=2, max=50, minMessage="user.lastname.minLength", maxMessage="user.lastname.maxLength" )
     */
    private $lastname;

    /**
     * @var string email
     * @JMS\Type("string")
     * @Assert\NotBlank( message="user.email.notBlank")
     * @Assert\Email( message="user.email.invalid", checkMX=false, checkHost=false )
     * @Assert\Length( max=60, maxMessage="user.email.maxLength" )
     */
    private $email;
    
    /**
     * @var string email
     * @JMS\Type("string")
     * @Assert\Length( max=10, maxMessage="user.addressPostcode.maxLength" )
     */
    private $postcode;

    /**
     * @var string clientLastName
     * @JMS\Type("string")
     * @Assert\NotBlank( message="client.lastname.notBlank" )
     * @Assert\Length(min = 2, minMessage= "client.lastname.minMessage", max=50, maxMessage= "client.lastname.maxMessage")
     */
    private $clientLastname;


    /**
     * @var string caseNumber
     * @JMS\Type("string")
     * @Assert\NotBlank( message="client.caseNumber.notBlank")
     * @Assert\Length(min = 2, minMessage= "client.caseNumber.minMessage", max=20, maxMessage= "client.caseNumber.maxMessage")
     */
    private $caseNumber;

    /**
     * @return string
     */
    public function getFirstname()
    {
        return $this->firstname;
    }

    /**
     * @param string $firstname
     */
    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;
    }

    /**
     * @return string
     */
    public function getLastname()
    {
        return $this->lastname;
    }

    /**
     * @param string $lastname
     */
    public function setLastname($lastname)
    {
        $this->lastname = $lastname;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }
    
    /**
     * @return string
     */
    public function getPostcode()
    {
        return $this->postcode;
    }

    /**
     * @param string $postcode
     */
    public function setPostcode($postcode)
    {
        $this->postcode = $postcode;
    }
    
    /**
     * @return string
     */
    public function getClientLastname()
    {
        return $this->clientLastname;
    }

    /**
     * @param string $clientLastname
     */
    public function setClientLastname($clientLastname)
    {
        $this->clientLastname = $clientLastname;
    }

    /**
     * @return string
     */
    public function getCaseNumber()
    {
        return $this->caseNumber;
    }

    /**
     * @param string $caseNumber
     */
    public function setCaseNumber($caseNumber)
    {
        $this->caseNumber = $caseNumber;
    }

    public function toArray()
    {
        return [
            'deputy_firstname' => $this->firstname,
            'deputy_lastname' => $this->lastname,
            'deputy_email' => $this->email,
            'deputy_postcode' => $this->postcode,
            'client_lastname' => $this->clientLastname,
            'client_case_number' => $this->caseNumber
        ];
    }


}