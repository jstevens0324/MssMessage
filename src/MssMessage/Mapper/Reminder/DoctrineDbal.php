<?php

namespace MssMessage\Mapper\Reminder;

use Doctrine\DBAL\Connection,
    MssMessage\Message;

class DoctrineDbal implements MapperInterface
{
    /**
     * @var Doctrine\DBAL\Connection
     */
    private $conn;

    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }

    public function saveMessageReminder(Message $message)
    {
        $extra = $message->getExtraData();

        foreach($extra['reminders'] as $rdata) {
            $data  = array(
                'dsid'        => $rdata['dsid'],
                'reminderRid' => $rdata['rid'],
                'messageId'   => $message->getId()
            );

            $this->conn->insert('message_reminder', $data);
        }
    }
}