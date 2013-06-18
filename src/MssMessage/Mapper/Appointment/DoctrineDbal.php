<?php

namespace MssMessage\Mapper\Appointment;

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

    public function saveMessageAppointment(Message $message)
    {
        $extra = $message->getExtraData();

        $data  = array(
            'dsid'           => $extra['dsid'],
            'appointmentRid' => $extra['appointmentRid'],
            'messageId'      => $message->getId()
        );

        $this->conn->insert('message_appointment', $data);
    }
}