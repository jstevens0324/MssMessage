<?php

namespace MssMessage\Service;

/*
 * @formatter:off
 */

use InvalidArgumentException,
    MssMessage\Mapper\Winusers\MapperInterface,
    MssMessage\MergewordSet,
    MssMessage\Message,
    MssMessage\Recipient;

/*
 *@formatter:on
 */

class Winusers
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
	 * @var MssMessage\Mapper\Winusers\MapperInterface
	 */
	private $mapper;

	public function __construct(Messenger $messenger, Mergeword $mergeword, MapperInterface $mapper)
	{
		$this->messenger = $messenger;
		$this->mergeword = $mergeword;
		$this->mapper = $mapper;
	}

    public function queueBatch(array $data)
    {
        if (!isset($data['patients'])) {
            throw new InvalidArgumentException('patients is a required key');
        }

        if (!isset($data['dsid'])) {
            throw new InvalidArgumentException('dsid is a required key');
        }

        // Patients are stored as a serialized PHP array
        if (false === ($patients = unserialize($data['patients']))) {
            throw new InvalidArgumentException('patients should be a serialized array');
        }
        unset($data['patients']);

        // Patient results should be unique already but force it anyway
        $patients = array_unique($patients);
        $patients = $this->mapper->getPatientList($data['dsid'], $patients);

	if (empty($data['contactTypeId'])) {
		unset($data['contactTypeId']);
	}

        // Iterate through all patients, merge with data (for mergewords), and send message.
        $messages = array();
        foreach($patients as $row) {
            $row     = array_merge($row, $data);
            $message = $this->createMessage($row);

            if (false !== $message) {
                $messages[] = $message;
            }
        }

        $this->messenger->events()->attach('queue.pre', array($this, 'cbAppendOptOut'));
        $this->messenger->queueBatch($messages);
    }


	/**
	 * Callback function to append opt-out information to a message. Pretty slick!
	 *
	 * Only works for email messages.
	 */
	public function cbAppendOptOut($e)
	{
		$optOutLayout = file_get_contents(realpath(__DIR__ . '/../../../layout/opt-out/email.html'));
		$baseLink = 'http://www.vetlogic.biz/message/my-communications';

		foreach($e->getTarget() as $message)
		{
			if($message->getContactTypeId() != Message::CONTACT_TYPE_EMAIL)
			{
				continue;
			}

			$body = $message->getBody() . $optOutLayout;
			$words = $message->getExtraData();
			$words = $words['raw'];
			$set = $this->mergeword->findByCompanyId($message->getCompanyId());

			// Add opt-out link
			$recipient = $message->getRecipient();
			$isClient = ($recipient->getDsid() && $recipient->getClientRid());

			if($isClient)
			{
				$params = sprintf('dsid=%s&clientRid=%s&email=%s', $recipient->getDsid(), $recipient->getClientRid(), $recipient->getEmail());
			}
			else
			{
				$params = sprintf('recipientId=%s&email=%s', $recipient->getRecipientId(), $recipient->getEmail());
			}

			$data['transportId'] = Message::TRANSPORT_TYPE_VETLOGIC;

			$words['optOutLink'] = sprintf('%s?r=%s', $baseLink, base64_encode(urlencode($params)));

			$body = $this->mergeword->mergeFromArray($body, $set, $words);
			$message->setBody($body);
		}
	}

	protected function createMessage(array $data)
	{
		if(false === $this->validateData($data))
		{
			return false;
		}

		$messenger = $this->messenger;
		$mergeword = $this->mergeword;
		$set = $mergeword->findByCompanyId($data['companyId']);

		// Standard mergewords
		$data['body'] = $mergeword->mergeFromArray($data['body'], $set, $data);
		$data['subject'] = $mergeword->mergeFromArray($data['subject'], $set, $data);

		// force message type
		$data['messageTypeId'] = Message::MESSAGE_TYPE_GENERIC;


		$message = $this->messenger->createFromArray($data);

		return $message;
	}

	protected function validateData(array &$data)
	{
		if(!isset($data['companyId']) || !is_numeric($data['companyId']))
		{
			error_log('No company ID found for message.');
			return false;
		}

		if(!isset($data['subject']))
		{
			error_log('No subject found for message.');
			return false;
		}

		if(!isset($data['body']))
		{
			error_log('No body found for message.');
			return false;
		}

		if((isset($data['clinicEmail']) && !empty($data['clinicEmail'])) && (isset($data['clinicName']) && !empty($data['clinicName'])))
		{
			$data['sender'] = $data['clinicEmail'];
			$data['senderName'] = $data['clinicName'];
		}
		else
		if((isset($data['companyEmail']) && !empty($data['companyEmail'])) && (isset($data['companyName']) && !empty($data['companyName'])))
		{
			$data['sender'] = $data['companyEmail'];
			$data['senderName'] = $data['companyName'];
		}
		else
		if(isset($data['recipientAddress']) && !empty($data['recipientAddress']))
		{
			$data['sender'] = $data['recipientAddress'];
			$data['senderName'] = '';
		}
		else
		if(isset($data['sender']) && isset($data['senderName']))
		{; // intentionally left blank
		}

		if(empty($data['sender']) || empty($data['senderName']))
		{
			error_log('No sender information found for message.');
			return false;
		}
	}

}