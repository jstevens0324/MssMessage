<?php

namespace MssMessage\Mapper\Messenger;

use DateTime,
    Exception,
    PDO,
    Doctrine\DBAL\Connection,
    MssMessage\Message,
    MssMessage\Recipient,
    MssMessage\Service\Messenger;

class DoctrineDbal implements MapperInterface
{
    /**
     * @var Doctrine\DBAL\Connection
     */
    private $conn;

    /**
     * @var array
     */
    private $messages;

    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }

    public function findRecipientByAddress($address)
    {
        $sql = 'SELECT * FROM message_recipient WHERE address = ?';

        $stmt   = $this->conn->executeQuery($sql, array($address));
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $recipient = null;
        if ($result) {
            $recipient = new Recipient;
            $recipient->setRecipientId($result['id'])
                      ->setEmail($result['address']);
        }

        return $recipient;
    }

    public function createRecipient($address)
    {
        $this->conn->insert(
            'message_recipient',
            array('address' => $address)
        );

        $recipient = new Recipient;
        $recipient->setRecipientId($this->conn->lastInsertId())
                  ->setEmail($address);

        return $recipient;
    }

    public function queueBatch(array $messages)
    {
        $this->conn->beginTransaction();

        foreach($messages as $message) {
            if (!$message instanceof Message) {
                throw new InvalidArgumentException(
                    'instance of MssMessage\Message\Message expected'
                );
            }

            try {
                $this->doQueue($message);
            } catch (Exception $e) {
                $this->conn->rollback();
                throw $e;
            }
        }
    }

    public function saveBatch(array $messages)
    {
        $this->conn->beginTransaction();

        foreach($messages as $message) {
            if (!$message instanceof Message) {
                throw new InvalidArgumentException(
                    'instance of MssMessage\Message\Message expected'
                );
            }

            try {
                $this->doSave($message);
            } catch (Exception $e) {
                $this->conn->rollback();
                throw $e;
            }
        }
    }

    public function abort()
    {
        $this->conn->rollback();
    }

    public function commit()
    {
        $this->conn->commit();
    }

    protected function doSave(Message $message)
    {
        $data = $message->toArray();
        foreach($data as &$value) {
            if ($value instanceof DateTime) {
                $value = $value->format('Y-m-d H:i:s');
            }
        }

        if ($message->getId()) {
            $this->conn->update('message', $data, array('id' => $message->getId()));
        } else {
            $this->conn->insert('message', $data);
        }
        return $message;
    }

    protected function doQueue(Message $message)
    {
        $data = $message->toArray();

        if ($data['queuedAt'] instanceof DateTime) {
            $data['queuedAt'] = $data['queuedAt']->format('Y-m-d H:i:s');
        }

        $rdata = array(
            'recipientId' => $message->getRecipient()->getRecipientId(),
            'dsid'        => $message->getRecipient()->getDsid(),
            'clientRid'   => $message->getRecipient()->getClientRid(),
        );
        $data  = array_merge($rdata, $data);

        $this->conn->insert(Messenger::TABLE_MESSAGE, $data);
        $message->setId($this->conn->lastInsertId());
    }
}