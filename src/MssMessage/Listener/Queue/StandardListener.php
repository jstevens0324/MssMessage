<?php
namespace MssMessage\Listener\Queue;
use MssMessage\Entity\ContactType,
    MssMessage\Entity\MessageSent,
    Zend\EventManager\Event;

class StandardListener implements Listener
{
    public function queue(Event $e)
    {
        $message = $e->getTarget();
        $em      = $e->getParam('em');
        
        switch($message->getContactType()->getId()) {
            case ContactType::EMAIL:
                $ms = new MessageSent;
                $ms->setMessage($message);
                
                $message->sent()->add($ms);
                
                $em->persist($ms);
                break;
        }
    }
}
