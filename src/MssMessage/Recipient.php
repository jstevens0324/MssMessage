<?php

namespace MssMessage;

class Recipient extends AbstractModel
{
    /**
     * @var int
     */
    protected $dsid;

    /**
     * @var int
     */    
    protected $clientRid;

    /**
     * @var int
     */    
    protected $recipientId;

    /**
     * @var string
     */    
    protected $firstName;

    /**
     * @var string
     */    
    protected $lastName;

    /**
     * @var string
     */    
    protected $email;

    /**
     * @var string
     */    
    protected $homePhone;

    /**
     * @var string
     */    
    protected $mobilePhone;
    
    /**
     * @var string
     */
    protected $workPhone;
    
    /**
     * Constructor, set defaults.
     * 
     * @param array $input
     */
    public function __construct(array $input = array())
    {
        $this->fromArray($input);
    }

	/**
     * Get dsid
     *
     * @return int
     */
    public function getDsid()
    {
        return $this->dsid;
    }

	/**
     * Set dsid
     *
     * @param int $dsid
     * @return MssMessage\Recipient
     */
    public function setDsid($dsid)
    {
        $this->dsid = $dsid;
        return $this;
    }

	/**
     * Get clientRid
     *
     * @return int
     */
    public function getClientRid()
    {
        return $this->clientRid;
    }

	/**
     * Set clientRid
     *
     * @param int $clientRid
     * @return MssMessage\Recipient
     */
    public function setClientRid($clientRid)
    {
        $this->clientRid = $clientRid;
        return $this;
    }

	/**
     * Get recipientId
     *
     * @return int
     */
    public function getRecipientId()
    {
        return $this->recipientId;
    }

	/**
     * Set recipientId
     *
     * @param int $recipientId
     * @return MssMessage\Recipient
     */
    public function setRecipientId($recipientId)
    {
        $this->recipientId = $recipientId;
        return $this;
    }

	/**
     * Get firstName
     *
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

	/**
     * Set firstName
     *
     * @param string $firstName
     * @return MssMessage\Recipient
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;
        return $this;
    }

	/**
     * Get lastName
     *
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
    }

	/**
     * Set lastName
     *
     * @param string $lastName
     * @return MssMessage\Recipient
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;
        return $this;
    }

	/**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

	/**
     * Set email
     *
     * @param string $email
     * @return MssMessage\Recipient
     */
    public function setEmail($email)
    {
        $this->email = $email;
        return $this;
    }

	/**
     * Get homePhone
     *
     * @return string
     */
    public function getHomePhone()
    {
        return $this->homePhone;
    }

	/**
     * Set homePhone
     *
     * @param string $homePhone
     * @return MssMessage\Recipient
     */
    public function setHomePhone($homePhone)
    {
        $this->homePhone = $homePhone;
        return $this;
    }

	/**
     * Get mobilePhone
     *
     * @return string
     */
    public function getMobilePhone()
    {
        return $this->mobilePhone;
    }

	/**
     * Set mobilePhone
     *
     * @param string $mobilePhone
     * @return MssMessage\Recipient
     */
    public function setMobilePhone($mobilePhone)
    {
        $this->mobilePhone = $mobilePhone;
        return $this;
    }

	/**
     * Get workPhone
     *
     * @return string
     */
    public function getWorkPhone()
    {
        return $this->workPhone;
    }

	/**
     * Set workPhone
     *
     * @param string $workPhone
     * @return MssMessage\Recipient
     */
    public function setWorkPhone($workPhone)
    {
        $this->workPhone = $workPhone;
        return $this;
    }
}