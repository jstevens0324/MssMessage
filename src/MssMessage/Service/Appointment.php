<?php

namespace MssMessage\Service;

use InvalidArgumentException,
    MssMessage\Mapper\Appointment\MapperInterface,
    MssMessage\MergewordSet,
    MssMessage\Message,
    MssMessage\Recipient;

class Appointment
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
     * @var MssMessage\Mapper\Appointment\MapperInterface
     */
    private $mapper;

    public function __construct(Messenger $messenger, Mergeword $mergeword, MapperInterface $mapper)
    {
        $this->messenger = $messenger;
        $this->mergeword = $mergeword;
        $this->mapper    = $mapper;
    }

	/**
	 * Wrapper for queueBatch to setup PetWise specific data for appointments, namely
	 * mergewords for appointmentConfirm and appointmentCancel.
	 *
	 * @param array $appointments
	 * @return MssMessage\Service\Appointment
	 */
	public function queuePetwiseBatch(array $appointments)
	{
		error_log('MssMessage\Service\Appointment::queuePetwiseBatch()');

		foreach($appointments as &$appointment)
		{
			error_log("\tMssMessage\\Service\\Appointment::queuePetwiseBatch() appointment");
			$this->addConfirmAndCancelLinks($appointment);
		}

		return $this->queueBatch($appointments);
	}

	/**
	 * Simple wrapper around queueBatch that sets up eMinders (I hate you) specific settings.
	 *
	 * @param array $appointments
	 */
	public function queueEmindersBatch(array $appointments)
	{
		error_log('MssMessage\Service\Appointment::queueEmindersBatch()');

		foreach($appointments as &$appointment)
		{
			// skip if no sendzaId is set
			//if (!isset($appointment['sendzaId']) || empty($appointment['sendzaId'])) {
			//    unset($appointment[$key]);

			//    continue;
			// }

			//if (in_array($appointment['companyId'], $companyIdsForTesting)) {
			error_log("\tMssMessage\\Service\\Appointment::queueEmindersBatch() appointment");
			$this->addConfirmAndCancelLinks($appointment);
			//}

			// force the sender and sender name
			//$appointment['sender']     = $appointment['sendzaId'];
			//$appointment['senderName'] = 'MSS eMinders Service';

			// determine the contact type for the recipient
			if(isset($appointment['preferredContactTypeId']))
			{
				$appointment['contactTypeId'] = $appointment['preferredContactTypeId'];
				unset($appointment['preferredContactTypeId']);
			}
			else
			{
				$appointment['contactTypeId'] = Message::CONTACT_TYPE_EMAIL;
			}

			$appointment['transportId'] = Message::TRANSPORT_TYPE_EMINDERS;
		}

		$this->queueBatch($appointments);
	}

    /**
     * Queues appointmentss from an array of data (generally a result set from the DB).
     *
     * @param array $appointmentss
     * @return MssMessage\Service\Appointment
     */
    public function queueBatch(array $appointments)
    {
        $messages = array();
        foreach($appointments as &$appointment) {
            $message = $this->createMessage($appointment);

            if (false !== $message) {
                $messages[] = $message;
            }
        }

        //$this->messenger->events()->attach('queue.pre', array($this, 'cbAppendOptOut'));
        $this->messenger->events()->attach('queue.post', array($this, 'cbMessageAppointment'));
        $this->messenger->queueBatch($messages);

        return $this;
    }

    /**
     * This callback is called on post.queue of a message and will place a record in the
     * message_appointment table so that appointments are only sent once and responses can be
     * tracked. The event target can either be a single message object or an array of message
     * objects depending on if a single message or a batch was processed.
     *
     * This looks inefficient at first but the messenger service runs everything in a transaction
     * which is opened prior to calling events so even though invididual rows are being inserted
     * here they will be done in a single transaction.
     */
    public function cbMessageAppointment($e)
    {
        $messages = is_array($e->getTarget()) ? $e->getTarget() : array($e->getTarget());
        foreach($messages as $message) {
            $this->mapper->saveMessageAppointment($message);
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

    /**
     * Adds confirmation and cancellation links to the given appointment data
     *
     * @param array $appointment
     * @return void
     */
    protected function addConfirmAndCancelLinks(&$appointment)
    {
        $baseUrl = 'http://www.vetlogic.biz/appointment-response';

        $params = sprintf(
            'dsid=%s&appointmentRid=%s&patientRid=%s&clientRid=%s&value=',
            $appointment['dsid'],
            $appointment['appointmentRid'],
            $appointment['patientRid'],
            $appointment['clientRid']
        );

        $confirm = base64_encode(urlencode($params . 'confirm'));
        $cancel  = base64_encode(urlencode($params . 'cancel'));

        $appointment['appointmentConfirm'] = sprintf('%s?response=%s', $baseUrl, $confirm);
        $appointment['appointmentCancel']  = sprintf('%s?response=%s', $baseUrl, $cancel);
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

		// Smart replace appointmentConfirm and appointmentCancel links.
		// The regex below replaces appointmentConfirm and appointmentLink with an <a href> version
		// unless already inside a tag.
		$body = $data['body'];

		$body = preg_replace('/(?:\{appointmentConfirm\}(?!")|(?<!")\{appointmentConfirm\})/', '<a href="{appointmentConfirm}">Confirm</a>', $body);
		$body = preg_replace('/(?:\{appointmentCancel\}(?!")|(?<!")\{appointmentCancel\})/', '<a href="{appointmentCancel}">Cancel</a>', $body);
		$body = preg_replace('/(?:\{appointmentReschedule\}(?!")|(?<!")\{appointmentReschedule\})/', '<a href="{appointmentReschedule}">Reschedule</a>', $body);

		// Automatically append confirm/cancel if they don't exist
		if(!strstr($body, '{appointmentConfirm}'))
		{
			$body .= file_get_contents(realpath(__DIR__ . '/../../../layout/appointment/default-confirm-cancel.html'));
		}

		// Standard mergewords
		$data['body'] = $mergeword->mergeFromArray($body, $set, $data);
		$data['subject'] = $mergeword->mergeFromArray($data['subject'], $set, $data);

		// force message type
		$data['messageTypeId'] = Message::MESSAGE_TYPE_APPOINTMENT;

		$message = $this->messenger->createFromArray($data);

		// Set the extraData for the message callback so that reminders can be inserted into the
		// message_reminder table.
		$message->setExtraData(array('dsid' => $data['dsid'], 'appointmentRid' => $data['appointmentRid'], 'raw' => $data));

		return $message;
	}

    protected function validateData(array &$data)
    {
        // validate data
        if (!isset($data['companyId']) || !is_numeric($data['companyId'])) {
            error_log('No company ID found for message.');
            return false;
        }

        if (!isset($data['subject'])) {
            $data['subject'] = 'Upcoming appointment for {patientName}';
        }

        if (!isset($data['contactTypeId'])) {
            $data['contactTypeId'] = Message::CONTACT_TYPE_EMAIL;
        }

        if (!isset($data['body'])) {
            switch($data['contactTypeId']) {
                case Message::CONTACT_TYPE_EMAIL:
                    $data['body'] = file_get_contents(realpath(__DIR__ . '/../../../layout/appointment/email.html'));
                    break;
                case Message::CONTACT_TYPE_VOICE:
                    $data['body'] = file_get_contents(realpath(__DIR__ . '/../../../layout/appointment/voice.html'));
                    break;
                case Message::CONTACT_TYPE_SMS:
                    $data['body'] = file_get_contents(realpath(__DIR__ . '/../../../layout/appointment/sms.html'));
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
             error_log('No sender information found for message.');
             return false;
        }
    }
}