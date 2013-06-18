<?php

namespace MssMessage\Service;

use InvalidArgumentException,
    MssMessage\Mapper\Reminder\MapperInterface,
    MssMessage\MergewordSet,
    MssMessage\Message,
    MssMessage\Recipient;

class Reminder
{
    /**
     * @var MssMessage\Service\Messenger
     */
    private $messenger;

    /**
     * @var MssMessage\Service\Mergeword
     */
    private $mergeword;

    /**
     * @var MssMessage\Mapper\Reminder\MapperInterface
     */
    private $mapper;

    /**
     * @var array
     */
    private $countWord = array(
        1  => 'one',
        2  => 'two',
        3  => 'three',
        4  => 'four',
        5  => 'five',
        6  => 'six',
        7  => 'seven',
        8  => 'eight',
        9  => 'nine',
        10 => 'ten'
    );

    public function __construct(Messenger $messenger, Mergeword $mergeword, MapperInterface $mapper)
    {
        $this->messenger = $messenger;
        $this->mergeword = $mergeword;
        $this->mapper    = $mapper;
    }

    /**
     * Queues reminders from an array of data (generally a result set from the DB).
     * The reminders are sorted by patient and sent out with the potential for multiple
     * reminders per patient email. Additional mergewords {reminderCount} and {reminderList}
     * are processed as well.
     *
     * @param array $reminders
     */
    public function queueBatch(array $reminders)
    {
        $compiled = array();
        foreach($reminders as $reminder) {
            $pid = sprintf('%s:%s', $reminder['patientDsid'], $reminder['patientRid']);

            if (!isset($compiled[$pid])) {
                $compiled[$pid] = $reminder;
            }

            $compiled[$pid]['reminders'][] = array(
                'dsid'        => $reminder['dsid'],
                'rid'         => $reminder['reminderRid'],
                'description' => $reminder['reminderDescription'],
                'dueDate'     => $reminder['reminderDueDate']
            );
        }


        $messages = array();
        foreach($compiled as $c) {
            $message    = $this->createMessage($c);
            $messages[] = $message;
        }

        $this->messenger->events()->attach('queue.pre', array($this, 'cbAppendOptOut'));
        $this->messenger->events()->attach('queue.post', array($this, 'cbMessageReminder'));
        $this->messenger->queueBatch($messages);

        return $this;
    }

    /**
     * Simple wrapper around queueBatch that sets up eMinders (I hate you) specific settings.
     *
     * @param array $reminders
     */
    public function queueEmindersBatch(array $reminders)
    {
        foreach($reminders as $key => &$reminder) {
            // Skip if no sendzaId is set
            if (!isset($reminder['sendzaId']) || empty($reminder['sendzaId'])) {
                unset($reminders[$key]);

                continue;
            }

            // Force the sender and sender name
            $reminder['sender']     = $reminder['sendzaId'];
            $reminder['senderName'] = 'Mss eMinder Service';

            // Determine the contact type for the recipient
            if (isset($reminder['preferredContactTypeId']) && $reminder['preferredContactTypeId'] > 0) {
                $reminder['contactTypeId'] = $reminder['preferredContactTypeId'];
                unset($reminder['preferredContactTypeId']);
            } else {
                $reminder['contactTypeId'] = Message::CONTACT_TYPE_EMAIL;
            }

            $reminder['transportId'] = Message::TRANSPORT_TYPE_EMINDERS;
        }

        $this->queueBatch($reminders);
    }

    /**
     * This callback is called on post.queue of a message and will place a record in the
     * message_reminder table so that reminders are only sent once. The event target can
     * either be a single message object or an array of message objects depending on if a
     * single message or a batch was processed.
     *
     * This looks inefficient at first but the messenger service runs everything in a transaction
     * which is opened prior to calling events so even though invididual rows are being inserted
     * here they will be done in a single transaction.
     */
    public function cbMessageReminder($e)
    {
        $messages = is_array($e->getTarget()) ? $e->getTarget() : array($e->getTarget());
        foreach($messages as $message) {
            $this->mapper->saveMessageReminder($message);
        }
    }

    /**
     * Callback function to append opt-out information to a message. Pretty slick!
     *
     * Only works for email messages.
     */
    public function cbAppendOptOut($e)
    {
        $optOutLayout = file_get_contents(realpath(__DIR__ . '/../../../layout/opt-out/email.html'));
        $baseLink     = 'http://www.vetlogic.biz/message/my-communications';

        foreach($e->getTarget() as $message) {
            if ($message->getContactTypeId() != Message::CONTACT_TYPE_EMAIL) {
                continue;
            }

            $body  = $message->getBody() . $optOutLayout;
            $words = $message->getExtraData();
            $words = $words['raw'];
            $set   = $this->mergeword->findByCompanyId($message->getCompanyId());

            // Add opt-out link
            $recipient = $message->getRecipient();
            $isClient  = ($recipient->getDsid() && $recipient->getClientRid());

            if ($isClient) {
                $params = sprintf(
                    'dsid=%s&clientRid=%s&email=%s',
                    $recipient->getDsid(),
                    $recipient->getClientRid(),
                    $recipient->getEmail()
                );
            } else {
                $params = sprintf(
                    'recipientId=%s&email=%s',
                    $recipient->getRecipientId(),
                    $recipient->getEmail()
                );
            }

            $words['optOutLink'] = sprintf('%s?r=%s', $baseLink, base64_encode(urlencode($params)));

            $body = $this->mergeword->mergeFromArray($body, $set, $words);
            $message->setBody($body);
        }
    }

    protected function createMessage(array $data)
    {
        $this->validateData($data);

        $messenger = $this->messenger;
        $mergeword = $this->mergeword;
        $set       = $mergeword->findByCompanyId($data['companyId']);
        $reminders = $data['reminders'];

        // Clean up data so that it can be passed to MergewordService::mergeFromArray
        unset($data['reminders']);

        // Add the reminder count and reminder count word merge words.
        $data['reminderCountWord'] = $data['reminderCount'] = count($reminders);
        if (isset($this->countWord[$data['reminderCount']])) {
            $data['reminderCountWord'] = $this->countWord[$data['reminderCount']];
        }

        // Take the reminder list and turn it into a text/html representation using the list.html
        // file and replacing the mergewords "description" and "dueDate".

        // TODO: Someone could let the clinics assign a layout to the list which would make
        // customizing the email 100%.
        $rbody  = '';
        $layout = file_get_contents(realpath(__DIR__ . '/../../../layout/reminder/list.html'));

        foreach($reminders as $reminder) {
            $rbody .= PHP_EOL . $mergeword->mergeFromArray($layout, $set, $reminder);
        }

        $data['reminderList'] = $rbody;

        // Standard mergewords
        $data['body']          = $mergeword->mergeFromArray($data['body'], $set, $data);
        $data['subject']       = $mergeword->mergeFromArray($data['subject'], $set, $data);
        $data['messageTypeId'] = Message::MESSAGE_TYPE_REMINDER;

        $message = $this->messenger->createFromArray($data);

        // Set the extraData for the message callback so that reminders can be inserted into the
        // message_reminder table.
        $message->setExtraData(array(
            'reminders' => $reminders,
            'raw'       => $data
        ));

        return $message;
    }

    protected function validateData(array &$data)
    {
        // validate data
        if (!isset($data['reminders']) || !is_array($data['reminders'])) {
            throw new InvalidArgumentException('missing or invalid data for reminders');
        }

        if (!isset($data['companyId']) || !is_numeric($data['companyId'])) {
            throw new InvalidArgumentException('missing or invalid data for companyId');
        }

        if (!isset($data['subject'])) {
            $data['subject'] = 'Reminders for {patientName}';
        }

        if (!isset($data['contactTypeId'])) {
            $data['contactTypeId'] = Message::CONTACT_TYPE_EMAIL;
        }

        if (!isset($data['body'])) {
            switch($data['contactTypeId']) {
                case Message::CONTACT_TYPE_EMAIL:
                    $data['body'] = file_get_contents(realpath(__DIR__ . '/../../../layout/reminder/email.html'));
                    break;
                case Message::CONTACT_TYPE_VOICE:
                    $data['body'] = file_get_contents(realpath(__DIR__ . '/../../../layout/reminder/voice.html'));
                    break;
                case Message::CONTACT_TYPE_SMS:
                    $data['body'] = file_get_contents(realpath(__DIR__ . '/../../../layout/reminder/sms.html'));
                    break;
            }
        }

        if (!isset($data['sender']) || !isset($data['senderName'])) {
            if ((isset($data['clinicEmail']) && !empty($data['clinicEmail'])) &&
                (isset($data['clinicName']) && !empty($data['clinicName']))
            ) {
                $data['sender']     = $data['clinicEmail'];
                $data['senderName'] = $data['clinicName'];
            } else if ((isset($data['companyEmail']) && !empty($data['companyEmail'])) &&
                       (isset($data['companyName']) && !empty($data['companyName']))
            ) {
                $data['sender']     = $data['companyEmail'];
                $data['senderName'] = $data['companyName'];
            } else if (isset($data['sender']) && isset($data['senderName'])) {
                ; // intentionally left blank
            }
        }

        if (empty($data['sender']) || empty($data['senderName'])) {
            throw new InvalidArgumentException('missing or invalid sender data');
        }
    }
}