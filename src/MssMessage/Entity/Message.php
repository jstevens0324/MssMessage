<?php

namespace MssMessage\Entity;

use Doctrine\Common\Collections\ArrayCollection,
    Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\MappedSuperClass
 */
class Message
{
    /** 
     * @ORM\Id 
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    
    /**
     * @ORM\Column(type="integer") 
     */
    protected $priority;

    /** 
     * @ORM\Column(type="string",length=100) 
     */
    protected $sender;

    /** 
     * @ORM\Column(type="string",length=50) 
     */
    protected $senderName;
	
	/**
	 * @ORM\Column(type="datetime")
	 */
	protected $queuedAt;

	/**
	 * @ORM\Column(type="datetime", nullable=true)
	 */
	protected $sentAt;
	

    /** 
     * @ORM\Column(type="array") 
     */
    protected $recipients;

    /** 
     * @ORM\Column(type="string",length=100) 
     */
    protected $subject;

    /** 
     * @ORM\Column(type="text") 
     */
    protected $html;

    /** 
     * @ORM\Column(type="text"); 
     */
    protected $text;

    /** 
     * @ORM\Column(type="array") 
     */
    protected $mergeWords;
    
    public function __construct()
    {
        $this->priority   = 100;
        $this->senderName = '';
    }
}