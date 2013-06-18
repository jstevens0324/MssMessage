<?php

namespace MssMessage\Transport;

use Exception,
    MssMessage\Message,
    MssSendza\Cast,
    MssSendza\Recipient,
    MssSendza\Service\Caster;


class Eminders extends AbstractAdapter
{
    /**
     * @var MssSendza\Service\Caster
     */
    private $caster;

    public function __construct(Caster $caster)
    {
        $this->caster = $caster;
    }

    public function send(Message $message)
    {
        $subject = $message->getSubject();

        // Full body for html messages
        $body = $message->getBody();

        // Strip down body into simple text for sms/phone
        $text = strip_tags($body);
        $text = preg_replace('/\n{3,50}/', '', $text);
        $text = preg_replace('/\s{2,50}/', ' ', $text);

        $address = $this->getContactAddress($message);

        // During debugging just return and don't send anything
        if ($this->getDebug()) {
            return;
        }

        // Messages differ based on contact type
        // Text messages get split into 150 chars and have the message number added
        $casts = array();
        switch($message->getContactTypeId()) {
            case Message::CONTACT_TYPE_PHONE:
                $casts[] = array(
                    'subject'   => $subject,
                    'body'      => $body,
                    'recipient' => new Recipient\PhoneRecipient($address)
                );
                break;
            case Message::CONTACT_TYPE_EMAIL:
                $casts[] = array(
                    'subject'   => $subject,
                    'body'      => $body,
                    'recipient' => new Recipient\EmailRecipient($address)
                );
                break;
            case Message::CONTACT_TYPE_SMS:
                $text   = explode("\n", wordwrap($text, 145));
                $total  = count($text);

                foreach($text as $count => $text) {
                    if ($total > 1) {
                        if (($total - 1) == $count) {
                            $text = sprintf('(%d/%d) %s', $count + 1, $total, $text);
                        } else if ($count == 0) {
                            $text = sprintf('(%d/%d) %s ...', $count + 1, $total, $text);
                        } else {
                            $text = sprintf('(%d/%d) ... %s ...', $count + 1, $total, $text);
                        }
                    }

                    $casts[] = array(
                        'subject'   => $subject,
                        'body'      => $text,
                        'recipient' => new Recipient\SmsRecipient($address)
                    );
                }
                break;
            default:
                $result = 'Invalid contact type detected, message skipped';
        }

        // The result is returned and will contain success or an error message.
        $result = '';

        // Send them
        foreach($casts as $data) {
            $cast = new Cast;
            $cast->setSubject($data['subject'])
                 ->setBody($data['body'], $data['recipient']->getType())
                 ->addRecipient($data['recipient']);

            // Setup extra transport data (like tags for appointment)
            switch($message->getMessageTypeId()) {
                case Message::MESSAGE_TYPE_APPOINTMENT:
                    $cast->setRecordResponse(
                        'confirm_reschedule_cancel_optout',
                        array(
                            '{appointmentConfirm}'    => 'confirm',
                            '{appointmentCancel}'     => 'cancel',
                            '{appointmentReschedule}' => 'reschedule'
                        )
                    );
                    break;
            }

            try {
                $result .= $this->caster->send($cast, $message->getSender());
            } catch (Exception $e) {
                $result = sprintf('Exception: %s, %s', get_class($e), $e->getMessage());
            }
        }

        return $result;
    }
}