<?php

namespace MssMessage\Transport;

use Exception,
    InvalidArgumentException,
    MssMessage\Model\Message,
    Zend\Mail\Message as ZendMessage,
    Zend\Mail\Transport,
    Zend\Mime\Message as MimeMessage,
    Zend\Mime\Part as MimePart;

class ZendMail extends AbstractAdapter
{
    /**
     * @var Zend\Mail\Transport
     */
    private $tr;
    
    public function __construct(Transport $tr)
    {
        $this->tr = $tr;
    }
    
    public function send(Message $message)
    {
        // html message
        $html = new MimePart($message->getBody());
        $html->type = 'text/html';
        
        $body = new MimeMessage;
        $body->setParts(array($html));
        
        
        // compose zend message
        $zendMessage = new ZendMessage;
        $zendMessage->addFrom($message->getSender(), $message->getSenderName())
                    ->setSubject($message->getSubject())
                    ->setBody($body);

        foreach($message->getRecipientClients() as $client) {
            $zendMessage->addTo(
                $this->getContactAddress($message, $client),
                sprintf('%s %s', $client->getFirstName(), $client->getLastName())
            );
        }
        
        if ($this->getDebug()) {
            return;
        }
        
        try {
            $this->tr->send($zendMessage);
            return true;
        } catch (Exception $e) {
            return sprintf('Exception: %s, %s', get_class($e), $e->getMessage());
        }
    }
}