<?php

namespace MssMessage\Transport;

use Exception,
    InvalidArgumentException,
    MssMessage\Message,
    Swift_Mailer,
    Swift_Message,
    Swift_Transport;

class SwiftMailer extends AbstractAdapter
{
    /**
     * @var Swift_Mailer
     */
    private $mailer;

    public function __construct(Swift_Transport $tr)
    {
        $this->mailer = Swift_Mailer::newInstance($tr);
    }

    public function send(Message $message)
    {
        // compose zend message
        $swift = Swift_Message::newInstance();
        try {
            $swift->setFrom(array($message->getSender() => $message->getSenderName()))
                  ->setSubject($message->getSubject())
                  ->setBody($message->getBody())
                  ->addPart($message->getBody(), 'text/html');
        } catch (Exception $e) {
            return sprintf('Exception: %s, %s', get_class($e), $e->getMessage());
        }

        $client  = $message->getRecipient();
        $contact = $this->getContactAddress($message, $client);
        $name    = sprintf('%s %s', $client->getFirstName(), $client->getLastName());

        try {
            $swift->addTo($contact, $name);
        } catch (Exception $e) {
            return sprintf('Exception: %s, %s', get_class($e), $e->getMessage());
        }

        if ($this->getDebug()) {
            return;
        }

        try {
            if ($this->mailer->send($swift) == 1) {
                return 'Ok';
            } else {
                return 'Failed to deliver message';
            }
        } catch (Exception $e) {
            return sprintf('Exception: %s, %s', get_class($e), $e->getMessage());
        }
    }
}
