<?php

namespace MssMessage\Service;

use InvalidArgumentException,
    MssMessage\Message;

class Newsletter
{
    /**
     * @var MssMessage\Service\Messenger
     */
    private $messenger;

    /**
     * @var MssMessage\Service\Mergeword
     */
    private $mergeword;

    public function __construct(Messenger $messenger, Mergeword $mergeword)
    {
        $this->messenger = $messenger;
        $this->mergeword = $mergeword;
    }

    public function queueBatch(array $newsletters, array $recipients = array())
    {
        $messages = array();
        $dups     = array();
        foreach($newsletters as $newsletter) {
            // Skip duplicates
            $cid = sprintf('%d:%d', $newsletter['dsid'], $newsletter['clientRid']);
            if ((in_array($cid, $dups)) || in_array($newsletter['clientEmail'], $dups)) {
                continue;
            }

            $dups[] = $cid;
            $dups[] = $newsletter['clientEmail'];

            $message = $this->createMessage($newsletter);

            // Add raw data to "extra" message data so that the opt-out callback
            // has the information required to merge the data.
            $message->setExtraData(array($newsletter));

            $messages[] = $message;
        }

        // Setup plain newsletter data using the newsletters array.
        // List recipients don't have access to a lot of merge words that are available normally.
        $plainNewsletter = array(
            'newsletterId'          => $newsletter['newsletterId'],
            'subject'               => $newsletter['subject'],
            'body'                  => $newsletter['body'],
            'companyId'             => $newsletter['companyId'],
            'companyName'           => $newsletter['companyName'],
            'companyEmail'          => $newsletter['companyEmail'],
            'companyPhoneNumber'    => $newsletter['companyPhoneNumber'],
            'companyAddressLineOne' => $newsletter['companyAddressLineOne'],
            'companyAddressLineTwo' => $newsletter['companyAddressLineTwo'],
            'companyCity'           => $newsletter['companyCity'],
            'companyState'          => $newsletter['companyState'],
            'companyCountry'        => $newsletter['companyCountry'],
            'companyZip'            => $newsletter['companyZip'],
        );
        foreach($recipients as $recipient) {
            // Skip duplicates
            if (in_array($recipient['recipientAddress'], $dups)) {
                continue;
            }

            $messages[] = $this->createMessage(array_merge($plainNewsletter, $recipient));
        }

        $this->messenger->events()->attach('queue.pre', array($this, 'cbAppendOptOut'));
        $this->messenger->queueBatch($messages);

        return $this;
    }

    /**
     * Callback function to append opt-out information to a message. Pretty slick!
     *
     * Only works for email messages.
     */
    public function cbAppendOptOut($e)
    {
       // $optOutLayout = file_get_contents(realpath(__DIR__ . '/../../../layout/opt-out/email.html'));
        $optOutLayout = "";
	$baseLink     = 'http://www.vetlogic.biz/message/my-communications';

        foreach($e->getTarget() as $message) {
            if ($message->getContactTypeId() != Message::CONTACT_TYPE_EMAIL) {
                continue;
            }

            $body  = $message->getBody() . $optOutLayout;
            /*$words = $message->getExtraData();
            $words = $words['raw'];
            $set   = $this->mergeword->findByCompanyId($message->getCompanyId());*/

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

	    $unsub = sprintf('%s?r=%s', $baseLink, base64_encode(urlencode($params)));
            $unsubLink = "<p>To unsubscribe, please click  <a target=\"_blank\" href=\"".$unsub."\">here</a></p>";


           // $words['optOutLink'] = "";//sprintf('%s?r=%s', $baseLink, base64_encode(urlencode($params)));

            //$body = $this->mergeword->mergeFromArray($body, $set, $words);
            $body .= $unsubLink;

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
        $data['messageTypeId'] = Message::MESSAGE_TYPE_NEWSLETTER;

        $message = $this->messenger->createFromArray($data);

        // Set the extraData for the message callback so that reminders can be inserted into the
        // message_reminder table.
        $message->setExtraData(array(
            'raw' => $data
        ));

        return $message;
    }

    protected function validateData(array &$data)
    {
        if (!isset($data['companyId']) || !is_numeric($data['companyId'])) {
            throw new InvalidArgumentException('missing or invalid data for companyId');
        }

        if (!isset($data['subject'])) {
            throw new InvalidArgumentException('missing data for subject');
        }

        if (!isset($data['contactTypeId'])) {
            $data['contactTypeId'] = Message::CONTACT_TYPE_EMAIL;
        }

        if (!isset($data['body'])) {
            throw new InvalidArgumentException('missing data for body');
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
        } else if (isset($data['recipientAddress']) && !empty($data['recipientAddress'])) {
            $data['sender']     = $data['recipientAddress'];
            $data['senderName'] = '';
        } else if (isset($data['sender']) && isset($data['senderName'])) {
            ; // intentionally left blank
        }

        if (empty($data['sender']) || empty($data['senderName'])) {
            throw new InvalidArgumentException('missing or invalid sender data');
        }
    }
}