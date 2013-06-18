<?php
namespace MssMessage\Service;
use InvalidArgumentException,
    RuntimeException,
    Doctrine\ORM\EntityManager,
    MssCompany\Entity\Clinic,
    MssCompany\Entity\Company,
    MssMessage\Entity\Message,
    MssMessage\Entity\MessageType,
    MssMessage\Listener,
    Zend\EventManager\EventCollection,
    Zend\EventManager\EventManager;
    
require_once __DIR__ . '/../../html2text.php';

class Sender
{
    /**
     * @var Doctrine\ORM\EntityManager
     */
    protected $entityManager;
    
    public function __construct(EntityManager $em)
    {
        $this->entityManager = $em;
    }
    
    /**
     * EventManager
     * 
     * @var Zend\EventManager\EventManager
     */
    protected $events;
    
    public function queue(array $data)
    {
        $message = $this->createMessage($data);
        $this->events()->trigger('message.queue', $message, array('em' => $this->getEntityManager()));
        
        $this->getEntityManager()->persist($message);
        $this->getEntityManager()->flush($message);
    }
    
    public function events()
    {
        if (!$this->events instanceof EventCollection) {
            $this->setEventManager(new EventManager(array(__CLASS__, get_class($this))));
            $this->setDefaultListeners();
        }
        return $this->events;
    }
    
    public function setEventManager(EventCollection $events)
    {
        $this->events = $events;
    }
    
    public function getEntityManager()
    {
        return $this->entityManager;
    }
    
    protected function createMessage(array $data)
    {
        $this->processData($data);
        
        $this->events()->trigger('message.create.pre', $data);
        
        $message = new Message;
        $message->setCompany($data['company'])
                ->setSubject($data['subject'])
                ->setHtml($data['html'])
                ->setText($data['text'])
                ->setMessageType($data['messageType'])
                ->setContactType($data['contactType'])
                ->setRecipients($data['recipients'])
                ->setSender($data['sender'])
                ->setSenderName($data['senderName'])
                ->setMergeWords($data['mergeWords']);
                
        $this->events()->trigger('message.create.post', $message);
                
        return $message;
    }
    
    protected function processData(array &$data)
    {
        $html = array_key_exists('html', $data);
        $text = array_key_exists('text', $data);
        $body = array_key_exists('body', $data);
        
        // check if company exists
        $clinic  = isset($data['clinic']) && $data['clinic'] instanceof Clinic;
        $company = $clinic || (isset($data['company']) && $data['company'] instanceof Company);
        
        if (!$company) {
            $this->missingRequiredData('company');
        } else if ($clinic && !isset($data['company'])) {
            $data['company'] = $data['clinic']->getDataSource()->getCompany();
        }
        
        // check body (html or text is allowed as well)
        if ($html && !$text) {
            $data['text'] = convert_html_to_text($data['html']);
        } else if ($text && !$html) {
            $data['html'] = $data['text'];
        } else if ($body) {
            $data['html'] = $data['body'];
            $data['text'] = convert_html_to_text($data['html']);
        } else {
            $this->missingRequiredData('body, html, or text');
        }
        
        if ($body) {
            unset($data['body']);
        }
        
        // subject
        if (!array_key_exists('subject', $data)) {
            $this->missingRequiredData('subject');
        }
        
        // contact type
        $data['contactType'] = $this->events()->trigger('contact.type', $data)->first();
        
        if (is_numeric($data['contactType'])) {
            $data['contactType'] = $this->getEntityManager()->getReference(
                'MssMessage\Entity\ContactType',
                $data['contactType']
            );
        }
        
        // message type
        $data['messageType'] = $this->events()->trigger('message.type', $data)->first();
        
        if (is_numeric($data['messageType'])) {
            $data['messageType'] = $this->getEntityManager()->getReference(
                'MssMessage\Entity\MessageType',
                $data['messageType']
            );
        }
        
        // check recipients (recipient, singular, is allowed as well)
        $data['recipients'] = $this->events()->trigger('recipients', $data)->first();
        
        if (empty($data['recipients'])) {
            $this->missingRequiredData('recipient(s) or client(s)');
        }
        
        // sender information
        $data['sender']     = $this->events()->trigger('sender',$data)->first();
        $data['senderName'] = $this->events()->trigger('sender.name', $data)->first();
        
        // create merge words
        if (!array_key_exists('mergeWords', $data)) {
            $data['mergeWords'] = array();
        }
        
        $patient = isset($data['patient']) && $data['patient'] instanceof Patient;
        $client  = $patient || (isset($data['client']) && $data['client'] instanceof Client);
        
        $data['mergeWords'] = array_merge(
            $data['mergeWords'],
            $this->getCompanyMergeWords($data['company'])
        );
        
        if ($clinic) {
            $data['mergeWords'] = array_merge(
                $data['mergeWords'],
                $this->getClinicMergeWords($data['clinic'])
            );
        }
    }

    protected function getClinicMergeWords(Clinic $clinic)
    {
        $words = array(
            'clinicName'        => $clinic->getName(),
            'clinicPhoneNumber' => $clinic->GetPhoneNumber(),
            'clinicFaxNumber'   => $clinic->getFaxNumber(),
            'clinicEmail'       => $clinic->getEmail(),
        );
        
        if ($clinic->getAddress()) {
            $words = array_merge($words, array(
                'clinicAddressLineOne' => $clinic->getAddress()->getAddressLineOne(),
                'clinicAddressLineTwo' => $clinic->getAddress()->getAddressLineTwo(),
                'clinicCity'           => $clinic->getAddress()->getCity(),
                'clinicState'          => $clinic->getAddress()->getState(),
                'clinicCountry'        => $clinic->getAddress()->getCountry(),
                'clinicZip'            => $clinic->getAddress()->getZip(),
            ));
        }
        
        return $words;
    }

    protected function getCompanyMergeWords(Company $company)
    {
        return array(
            'companyName'  => $company->getName(),
            'companyPhone' => $company->getPhoneNumber(),
            'companyEmail' => $company->getEmail()
        );
    }
    
    protected function missingRequiredData($name)
    {
        throw new InvalidArgumentException(sprintf('
            missing required data: "%s"',
            $name
        ));
    }
    
    protected function setDefaultListeners()
    {
        $this->events()->attach('message.create.pre', array(new Listener\Data\StandardListener, 'preMessageCreate'));
        $this->events()->attach('message.create.post', array(new Listener\Data\StandardListener, 'postMessageCreate'));
        $this->events()->attach('contact.type', array(new Listener\Data\StandardListener, 'contactType'));
        $this->events()->attach('message.type', array(new Listener\Data\StandardListener, 'messageType'));
        $this->events()->attach('client.recipient', array(new Listener\Data\StandardListener, 'clientRecipient'));
        $this->events()->attach('sender', array(new Listener\Data\StandardListener, 'sender'));
        $this->events()->attach('sender.name', array(new Listener\Data\StandardListener, 'senderName'));
        $this->events()->attach('message.queue', array(new Listener\Queue\StandardListener, 'queue'));
    }
}
