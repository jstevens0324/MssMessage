<?php
namespace MssMessage\Listener\Data;
use InvalidArgumentException,
    MssClient\Entity\Client,
    MssMessage\Entity\MessageType,
    MssSendza\Entity\ContactType,
    Zend\EventManager\Event;

class StandardListener implements Listener
{
    public function contactType(Event $e)
    {
        $data        = $e->getTarget();
        $contactType = isset($data['contactType']) ? $data['contactType'] : null;
        $clinic      = isset($data['clinic']) ? $data['clinic'] : null;
        $client      = isset($data['client']) ? $data['client'] : null;
        
        if (!$clinic || !$clinic->isSendzaEnabled() || !$clinic->getSendzaId()) {
            throw new RuntimeException('clinic is missing or Sendza is not enabled');
        }
        
        if ($contactType) {
            return $contactType;
        } else if ($client && $client->getPreferredContactType()) {
            return $client->getPreferredContactType();
        } else {
            return ContactType::EMAIL;
        }
    }
    
    public function messageType(Event $e)
    {
        return MessageType::GENERIC;
    }
    
    public function recipients(Event $e)
    {
        $data = $e->getTarget();
        
        if (array_key_exists('recipients', $data)) {
            return $data['recipients'];
        } else if (array_key_exists('recipient', $data)) {
            return array($data['recipient'] => '');
        } else if (array_key_exists('client', $data)) {
            if (is_array($data['client'])) {
                // todo: make me work
            } else if ($data['client'] instanceof Client) {
                switch($data['contactType']->getId()) {
                    case ContactType::EMAIL:
                        $contact = $data['client']->getEmail();
                        break;
                    case ContactType::PHONE:
                        $contact = $data['client']->getMobilePhone();
                        
                        if (!$contact) {
                            $contact = $data['client']->getHomePhone();
                        }
                        
                        if (!$contact) {
                            $contact = $data['client']->getWorkPhone();
                        }
                        break;
                    case ContactType::SMS:
                        $contact = $data['client']->getMobilePhone();
                        break;
                }

                if ($contact) {
                    return array($contact => $data['client']->getFullName());
                }
            } else {
                throw new InvalidArgumentException('unknown datatype for client');
            }
        }
        
        return array();
    }
    
    public function sender(Event $e)
    {
        $data    = $e->getTarget();
        $company = $data['company'];
        $clinic  = isset($data['clinic']) ? $data['clinic'] : null;
        
        return ($clinic && $clinic->getEmail()) ? $clinic->getEmail() : $company->getEmail();
    }
    
    public function senderName(Event $e)
    {
        $data    = $e->getTarget();
        $company = $data['company'];
        $clinic  = isset($data['clinic']) ? $data['clinic'] : null;
        
        return $clinic ? $clinic->getName() : $company->getName();
    }
    
    public function preMessageCreate(Event $e)
    {}
    
    public function postMessageCreate(Event $e)
    {}
}