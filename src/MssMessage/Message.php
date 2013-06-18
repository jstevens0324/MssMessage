<?php

namespace MssMessage;

use DateTime,
    InvalidArgumentException;

class Message extends AbstractModel
{
    const ERROR_INVALID_CONTACT = 'Invalid contact type';
    const ERROR_INVALID_MESSAGE = 'Invalid message type';
    const ERROR_NO_BODY         = 'No body was set';
    const ERROR_NO_COMPANY      = 'No company was set.';
    const ERROR_NO_RECIPIENT    = 'No recipient was set';
    const ERROR_NO_SENDER       = 'No sender was set';
    const ERROR_NO_SENDER_NAME  = 'No sender name was set';
    const ERROR_NO_SUBJECT      = 'No subject was set';

    const CONTACT_TYPE_EMAIL = 1;
    const CONTACT_TYPE_SMS   = 2;
    const CONTACT_TYPE_PHONE = 3;

    const MESSAGE_TYPE_GENERIC             = 1;
    const MESSAGE_TYPE_APPOINTMENT         = 2;
    const MESSAGE_TYPE_REMINDER            = 3;
    const MESSAGE_TYPE_REMINDER_OVERDUE    = 4;
    const MESSAGE_TYPE_NEWSLETTER          = 5;
    const MESSAGE_TYPE_CONTACT_US          = 6;
    const MESSAGE_TYPE_APPOINTMENT_REQUEST = 7;
    const MESSAGE_TYPE_BOARDING_REQUEST    = 8;
    const MESSAGE_TYPE_REFILL_REQUEST      = 9;
    const MESSAGE_TYPE_CUSTOM_FORM         = 10;
    const MESSAGE_TYPE_BIRTHDAY            = 11;
    const MESSAGE_TYPE_ACCOUNT_CREATED     = 12;

    const TRANSPORT_TYPE_VETLOGIC = 1;
    const TRANSPORT_TYPE_EMINDERS = 2;

    /**
     * @var array
     */
    private $validContactTypes = array(
        self::CONTACT_TYPE_SMS,
        self::CONTACT_TYPE_PHONE,
        self::CONTACT_TYPE_EMAIL
    );

    private $validMessageTypes = array(
        self::MESSAGE_TYPE_ACCOUNT_CREATED,
        self::MESSAGE_TYPE_APPOINTMENT,
        self::MESSAGE_TYPE_APPOINTMENT_REQUEST,
        self::MESSAGE_TYPE_BIRTHDAY,
        self::MESSAGE_TYPE_BOARDING_REQUEST,
        self::MESSAGE_TYPE_CONTACT_US,
        self::MESSAGE_TYPE_CUSTOM_FORM,
        self::MESSAGE_TYPE_GENERIC,
        self::MESSAGE_TYPE_NEWSLETTER,
        self::MESSAGE_TYPE_REFILL_REQUEST,
        self::MESSAGE_TYPE_REMINDER,
        self::MESSAGE_TYPE_REMINDER_OVERDUE
    );

    private $validTransportTypes = array(
        self::TRANSPORT_TYPE_EMINDERS,
        self::TRANSPORT_TYPE_VETLOGIC
    );

    protected $arrayData = array(
        'id',
        'subject',
        'sender',
        'senderName',
        'body',
        'queuedAt',
        'sentAt',
        'result',
        'priority',
        'transportId',
        'messageTypeId',
        'contactTypeId',
        'companyId',
    );

    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $subject;

    /**
     * @var string
     */
    protected $sender;

    /**
     * @var string
     */
    protected $senderName;

    /**
     * @var string
     */
    protected $body;

    /**
     * @var DateTime
     */
    protected $queuedAt;

    /**
     * @var DateTime
     */
    protected $sentAt;

    /**
     * @var string
     */
    protected $result;

    /**
     * @var int
     */
    protected $priority;

    /**
     * @var int
     */
    protected $messageTypeId;

    /**
     * @var int
     */
    protected $contactTypeId;

    /**
     * @var int
     */
    protected $transportId;

    /**
     * @var int
     */
    protected $companyId;

    /**
     * @var MssMessage\Recipient
     */
    protected $recipient;

    /**
     * Additional data used in callbacks.
     *
     * @var array
     */
    protected $extraData;

    /**
     * Constructor, set message defaults.
     *
     * @param array $defaults
     */
    public function __construct(array $input = array())
    {
        $defaults = array(
            'typeId'        => self::MESSAGE_TYPE_GENERIC,
            'transportId'   => self::TRANSPORT_TYPE_VETLOGIC,
            'contactTypeId' => self::CONTACT_TYPE_EMAIL,
            'queuedAt'      => new DateTime,
            'priority'      => 99,
            'extraData'     => array(),
        );
        $defaults = array_merge($defaults, $input);

        $this->fromArray($defaults);
    }

    /**
     * Checks the message for validity.
     *
     * @return true if message is valid otherwise an array of errors
     */
    public function isValid()
    {
        $errors  = array();

        if (!in_array($this->getContactTypeId(), $this->validContactTypes)) {
            $errors[] = self::ERROR_INVALID_CONTACT;
        }

        if (!in_array($this->getMessageTypeId(), $this->validMessageTypes)) {
            $errors[] = self::ERROR_INVALID_MESSAGE;
        }

        if (!$this->getCompanyId()) {
            $errors[] = self::ERROR_NO_COMPANY;
        }

        if (!$this->getSubject()) {
            $errors[] = self::ERROR_NO_SUBJECT;
        }

        if (!$this->getBody()) {
            $errors[] = self::ERROR_NO_BODY;
        }

        if (!$this->getSender()) {
            $errors[] = self::ERROR_NO_SENDER;
        }

        if (!$this->getSenderName()) {
            $errors[] = self::ERROR_NO_SENDER_NAME;
        }

        if (($recipient = $this->getRecipient())) {
            if (!$recipient->getRecipientId() && (!$recipient->getDsid() || !$recipient->getClientRid())) {
                $errors[] = self::ERROR_NO_RECIPIENT;
            }
        } else {
            $errors[] = self::ERROR_NO_RECIPIENT;
        }

        return (0 === count($errors)) ? true : $errors;
    }

	/**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set id
     *
     * @return int
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

	/**
     * Get priority
     *
     * @return int
     */
    public function getPriority()
    {
        return $this->priority;
    }

	/**
     * Set priority
     *
     * @param int $priority
     * @return MssMessage\Model\Message
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;
        return $this;
    }

	/**
     * Get subject
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

	/**
     * Set subject
     *
     * @param string $subject
     * @return MssMessage\Model\Message
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
        return $this;
    }

	/**
     * Get sender
     *
     * @return string
     */
    public function getSender()
    {
        return $this->sender;
    }

	/**
     * Set sender
     *
     * @param string $sender
     * @return MssMessage\Model\Message
     */
    public function setSender($sender)
    {
        $this->sender = $sender;
        return $this;
    }

	/**
     * Get senderName
     *
     * @return string
     */
    public function getSenderName()
    {
        return $this->senderName;
    }

	/**
     * Set senderName
     *
     * @param string $senderName
     * @return MssMessage\Model\Message
     */
    public function setSenderName($senderName)
    {
        $this->senderName = $senderName;
        return $this;
    }

	/**
     * Get body
     *
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

	/**
     * Set body
     *
     * @param string $body
     * @return MssMessage\Model\Message
     */
    public function setBody($body)
    {
        $this->body = $body;
        return $this;
    }

	/**
     * Get queuedAt
     *
     * @return DateTime
     */
    public function getQueuedAt()
    {
        return $this->queuedAt;
    }

	/**
     * Set queuedAt
     *
     * @param DateTime $queuedAt
     * @return MssMessage\Model\Message
     */
    public function setQueuedAt($queuedAt)
    {
        if (is_string($queuedAt)) {
            $queuedAt = new DateTime($queuedAt);
        }
        $this->queuedAt = $queuedAt;
        return $this;
    }

	/**
     * Get sentAt
     *
     * @return DateTime
     */
    public function getSentAt()
    {
        return $this->sentAt;
    }

	/**
     * Set sentAt
     *
     * @param DateTime $sentAt
     * @return MssMessage\Model\Message
     */
    public function setSentAt($sentAt)
    {
        if (is_string($sentAt)) {
            $sentAt = new DateTime($sentAt);
        }
        $this->sentAt = $sentAt;
        return $this;
    }

	/**
     * Get messageTypeId
     *
     * @return int
     */
    public function getMessageTypeId()
    {
        return $this->messageTypeId;
    }

	/**
     * Set messageTypeId
     *
     * @param int $messageTypeId
     * @return MssMessage\Model\Message
     */
    public function setMessageTypeId($messageTypeId)
    {
        $this->messageTypeId = $messageTypeId;
        return $this;
    }

	/**
     * Get contactTypeId
     *
     * @return int
     */
    public function getContactTypeId()
    {
        return $this->contactTypeId;
    }

	/**
     * Set contactTypeId
     *
     * @param int $contactTypeId
     * @return MssMessage\Model\Message
     */
    public function setContactTypeId($contactTypeId)
    {
        $this->contactTypeId = $contactTypeId;
        return $this;
    }

	/**
     * Get companyId
     *
     * @return int
     */
    public function getCompanyId()
    {
        return $this->companyId;
    }

	/**
     * Set companyId
     *
     * @param int $companyId
     * @return MssMessage\Model\Message
     */
    public function setCompanyId($companyId)
    {
        $this->companyId = $companyId;
        return $this;
    }

	/**
     * Get result
     *
     * @return string
     */
    public function getResult()
    {
        return $this->result;
    }

	/**
     * Set result
     *
     * @param string $result
     * @return MssMessage\Model\Message
     */
    public function setResult($result)
    {
        $this->result = $result;
        return $this;
    }

	/**
     * Get recipient
     *
     * @return MssMessage\Recipient
     */
    public function getRecipient()
    {
        return $this->recipient;
    }

	/**
     * Set recipient
     *
     * @param MssMessage\Recipient $recipient
     * @return MssMessage\Message
     */
    public function setRecipient($recipient)
    {
        if (is_array($recipient)) {
            $recipient = new Recipient($recipient);
        } else if ($recipient instanceof Recipient) {
            ; // intentionally left blank
        } else {
            throw new InvalidArgumentException('recipient should be an array or Recipient object');
        }

        $this->recipient = $recipient;
        return $this;
    }

	/**
     * Get extraData
     *
     * @return array
     */
    public function getExtraData()
    {
        return $this->extraData;
    }

	/**
     * Set extraData
     *
     * @param array $extraData
     * @return MssMessage\Message
     */
    public function setExtraData($extraData)
    {
        $this->extraData = $extraData;
        return $this;
    }

	/**
     * Get transportId
     *
     * @return int
     */
    public function getTransportId()
    {
        return $this->transportId;
    }

	/**
     * Set transportId
     *
     * @param int $transportId
     * @return MssMessage\Message
     */
    public function setTransportId($transportId)
    {
        $this->transportId = $transportId;
        return $this;
    }
}