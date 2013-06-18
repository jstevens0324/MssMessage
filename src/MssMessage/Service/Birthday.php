<?php

namespace MssMessage\Service;

use InvalidArgumentException,
	MssMessage\Mapper\Birthdate\MapperInterface,
    MssMessage\MergewordSet,
    MssMessage\Message,
    MssMessage\Recipient;

class Birthday
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
     * 
     * @var MssMessage\Mapper\Birthdate\MapperInterface,
     */
    private $mapper;

    public function __construct(Messenger $messenger, Mergeword $mergeword, MapperInterface $mapper)
    {
        $this->messenger = $messenger;
        $this->mergeword = $mergeword;
        $this->mapper    = $mapper;
    }

    /**
     * Queues birthdays from an array of data (generally a result set from the DB).
     *
     * @param array $birthdays
     */
    public function queueBatch(array $birthdays)
    {

        $messages = array();
        foreach($birthdays as $birthday) {
            $messages[] = $this->createMessage($birthday);
        }
  
        
        $this->messenger->events()->attach('queue.pre', array($this, 'cbAppendOptOut'));
        $this->messenger->events()->attach('queue.post', array($this, 'cbMessageBirthdate'));
        $this->messenger->queueBatch($messages);

        return $this;
    }
    

    public function cbMessageBirthdate($e)
    {
    	$messages = is_array($e->getTarget()) ? $e->getTarget() : array($e->getTarget());
    	foreach($messages as $message) {
    		$this->mapper->saveMessageBirthdate($message);
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

        // Standard mergewords
        $data['body']    = $mergeword->mergeFromArray($data['body'], $set, $data);
        $data['subject'] = $mergeword->mergeFromArray($data['subject'], $set, $data);

        // Force message type
        $data['messageTypeId'] = Message::MESSAGE_TYPE_BIRTHDAY;

        $message = $this->messenger->createFromArray($data);

        // Set the extraData for the message callback so that opt-out information can be added
        // to the message.
        $message->setExtraData(array(
        	'raw' => $data 	        
        ));

        return $message;
    }

    protected function validateData(array &$data)
    {
        // validate data
        if (!isset($data['patientBirthDate'])) {
            throw new InvalidArgumentException('missing or invalid data for patientBirthDate');
        }

        if (!isset($data['companyId']) || !is_numeric($data['companyId'])) {
            throw new InvalidArgumentException('missing or invalid data for companyId');
        }

        if (!isset($data['subject'])) {
            $data['subject'] = 'Happy Birthday to {patientName}';
        }

        if (!isset($data['contactTypeId'])) {
            $data['contactTypeId'] = Message::CONTACT_TYPE_EMAIL;
        }

        if (!isset($data['body'])) {
            switch($data['contactTypeId']) {
                case Message::CONTACT_TYPE_EMAIL:
                    $data['body'] = file_get_contents(realpath(__DIR__ . '/../../../layout/birthday/email.html'));
                    break;
                case Message::CONTACT_TYPE_VOICE:
                    $data['body'] = file_get_contents(realpath(__DIR__ . '/../../../layout/birthday/voice.html'));
                    break;
                case Message::CONTACT_TYPE_SMS:
                    $data['body'] = file_get_contents(realpath(__DIR__ . '/../../../layout/birthday/sms.html'));
                    break;
            }
        }

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

        if (empty($data['sender']) || empty($data['senderName'])) {
            throw new InvalidArgumentException('missing or invalid sender data');
        }
    }
}