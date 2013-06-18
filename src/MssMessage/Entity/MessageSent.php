<?php

namespace MssMessage\Entity;

use DateTime,
    Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\MappedSuperClass
 */
class MessageSent
{
	/** 
	 * @ORM\Id 
     * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id;

	/**
	 * @ORM\Column(type="text",nullable=true)
	 */
	protected $sendResult;

	/**
	 * @ORM\Column(type="datetime")
	 */
	protected $queued;

	/**
	 * @ORM\Column(type="datetime", nullable=true)
	 */
	protected $sent;
    
    public function __construct()
    {
        $this->queued = new DateTime('now');
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
     * Set id
     *
     * @param integer $id
     * @return MssMessage\Entity\MessageSent
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

	/**
     * Get sendResult
     *
     * @return string
     */
    public function getSendResult()
    {
        return $this->sendResult;
    }

	/**
     * Set sendResult
     *
     * @param string $sendResult
     * @return MssMessage\Entity\MessageSent
     */
    public function setSendResult($sendResult)
    {
        $this->sendResult = $sendResult;
        return $this;
    }

	/**
     * Get queued
     *
     * @return DateTime
     */
    public function getQueued()
    {
        return $this->queued;
    }

	/**
     * Set queued
     *
     * @param DateTime $queued
     * @return MssMessage\Entity\MessageSent
     */
    public function setQueued($queued)
    {
        $this->queued = $queued;
        return $this;
    }

	/**
     * Get sent
     *
     * @return DateTime
     */
    public function getSent()
    {
        return $this->sent;
    }

	/**
     * Set sent
     *
     * @param DateTime $sent
     * @return MssMessage\Entity\MessageSent
     */
    public function setSent($sent)
    {
        $this->sent = $sent;
        return $this;
    }
}