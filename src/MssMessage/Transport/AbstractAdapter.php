<?php

namespace MssMessage\Transport;

use MssMessage\Message;

abstract class AbstractAdapter
{
    const RESULT_OK              = 'Ok';
    const RESULT_NO_CONTACT_DATA = 'No contact information could be found for recipient';

    /**
     * @var bool
     */
    private $debug = false;

    abstract public function send(Message $message);

    /**
     * Get debug
     *
     * @return bool
     */
    public function getDebug()
    {
        return $this->debug;
    }

    /**
     * Set debug
     *
     * @param bool $debug
     * @return MssMessage\Transport\AbstractAdapter
     */
    public function setDebug($debug)
    {
        $this->debug = (bool) $debug;
        return $this;
    }

    /**
     * Returns the contact information for the recipient based on the contact type
     * assigned to the message.
     *
     * @param MssMessage\Message $message
     * $return string|null
     */
    protected function getContactAddress(Message $message)
    {
        $contactType = $message->getContactTypeId();
        $recipient   = $message->getRecipient();

        if ($contactType == Message::CONTACT_TYPE_EMAIL) {
            return $recipient->getEmail();
        }

        if ($contactType == Message::CONTACT_TYPE_SMS) {
            return $recipient->getMobilePhone();
        }

        if ($contactType == Message::CONTACT_TYPE_PHONE) {
            if ($recipient->getMobilePhone()) {
                return $recipient->getMobilePhone();
            }

            if ($recipient->getHomePhone()) {
                return $recipient->getHomePhone();
            }

            if ($recipient->getWorkPhone()) {
                return $recipient->getWorkPhone();
            }
        }

        return null;
    }
}