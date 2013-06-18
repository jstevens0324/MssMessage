<?php

namespace MssMessage\Service;

use DateTime,
    InvalidArgumentException,
    RuntimeException,
    MssMessage\Mapper\Messenger\MapperInterface,
    MssMessage\Message,
    MssMessage\Recipient,
    MssMessage\Transport\AbstractAdapter,
    Zend\EventManager\EventCollection,
    Zend\EventManager\EventManager;

class Messenger
{
    const TABLE_MESSAGE          = 'message';
    const TABLE_RECIPIENT_CLIENT = 'message_recipient_client';

    const MESSAGE_OK = 'Ok';

    const DEFAULT_BATCH = 50;

    /**
     * @var Zend\EventManager\EventCollection
     */
    protected $events;

    /**
     * @var MssMessage\Transport\Adapter
     */
    private $adapter = null;

    /**
     * @var MssMessage\Mapper\Messenger\MapperInterface
     */
    private $mapper;

    /**
     * @var array
     */
    private $messages;

    public function __construct(MapperInterface $mapper, AbstractAdapter $adapter = null)
    {
        if ($adapter) {
            $this->getTransportAdapter($adapter);
        }
        $this->setMapper($mapper);
    }

    /**
     * Get adapter
     *
     * @return MssMessage\Transport\AbstractAdapter
     */
    public function getTransportAdapter()
    {
        return $this->adapter;
    }

    /**
     * Set adapter
     *
     * @param MssMessage\Transport\AbstractAdapter $adapter
     * @return MssMessage\Service\Messenger
     */
    public function setTransportAdapter($adapter)
    {
        $this->adapter = $adapter;
        return $this;
    }

    /**
     * Get mapper
     *
     * @return MssMessage\Mapper\Messenger\MapperInterface
     */
    public function getMapper()
    {
        return $this->mapper;
    }

    /**
     * Set mapper
     *
     * @param MssMessage\Mapper\Messenger\MapperInterface $mapper
     * @return MssMessage\Service\Messenger
     */
    public function setMapper($mapper)
    {
        $this->mapper = $mapper;
        return $this;
    }

    /**
     * Finds or creates a recipient if one does not exist.
     *
     * @param  string $address
     * @return MssMessage\Recipient
     */
    public function findOrCreateRecipient($address)
    {
        if (!($recipient = $this->mapper->findRecipientByAddress($address))) {
            $recipient = $this->mapper->createRecipient($address);
        }

        return $recipient;
    }

    /**
     * Queues a batch of messages.
     *
     * @param array $messages
     */
    public function queueBatch(array $messages)
    {
        $this->events()->trigger('queue.pre', $messages);

        foreach($messages as $message) {
            if (!$message instanceof Message) {
                throw new InvalidArgumentException('invalid message type detected');
            }

            $this->validateMessage($message);
            $message->setQueuedAt(new DateTime);
        }

        $this->mapper->queueBatch($messages);
        $this->events()->trigger('queue.post', $messages);
        $this->mapper->commit();
    }

    /**
     * Sends a batch of messages.
     *
     * @param array           $messages Array of MssMessage\Message to send.
     * @param AbstractAdapter $adapter  The adapter to use to send messages. If none is set the
     *                                  default is used instead.
     */
    public function sendBatch(array $messages, AbstractAdapter $adapter = null)
    {
        $this->events()->trigger('send.pre', $messages);
        $this->validateAdapter($adapter);

        foreach($messages as $message) {
            if (!$message instanceof Message) {
                throw new InvalidArgumentException('invalid message type detected');
            }

            $this->validateMessage($message);
            $this->doSend($message, $adapter);
        }

        if (false === $adapter->getDebug()) {
            $this->mapper->saveBatch($messages);
        }

        $this->events()->trigger('send.post', $messages);

        if (false === $adapter->getDebug()) {
            $this->mapper->commit();
        }
    }

    /**
     * Set the event manager instance used by this context
     *
     * @param  EventCollection $events
     * @return mixed
     */
    public function setEventManager(EventCollection $events)
    {
        $this->events = $events;
        return $this;
    }

    /**
     * Retrieve the event manager
     *
     * Lazy-loads an EventManager instance if none registered.
     *
     * @return EventCollection
     */
    public function events()
    {
        if (!$this->events instanceof EventCollection) {
            $identifiers = array(__CLASS__, get_class($this));
            if (isset($this->eventIdentifier)) {
                if ((is_string($this->eventIdentifier))
                    || (is_array($this->eventIdentifier))
                    || ($this->eventIdentifier instanceof Traversable)
                ) {
                    $identifiers = array_unique(array_merge($identifiers, (array) $this->eventIdentifier));
                } elseif (is_object($this->eventIdentifier)) {
                    $identifiers[] = $this->eventIdentifier;
                }
                // silently ignore invalid eventIdentifier types
            }
            $this->setEventManager(new EventManager($identifiers));
        }
        return $this->events;
    }

    /**
     * Creates a message from an array and verifies that all the parts are available.
     *
     * @param array $data
     * @throws InvalidArgumentException on invalid or missing message data
     * @return Message
     */
    public function createFromArray(array $data)
    {
        // Convert data that matches into recipient data
        $rdata = array();
        foreach($data as $key => $value) {
            if (preg_match('/^(?:client|recipient)(\w+)$/', $key, $match)) {
                $rdata[lcfirst($match[1])] = $value;
            }
        }

        // Create using fromArray() method
        $message   = new Message($data);
        $recipient = new Recipient(array_merge($data, $rdata));

        $message->setRecipient($recipient);

        $this->validateMessage($message);

        return $message;
    }

    /**
     * Called by sendBatch() to send messages.
     *
     * @param MssMessage\Message                   $message
     * @param MssMessage\Transport\AbstractAdapter $adapter
     * @return void
     */
    protected function doSend(Message $message, AbstractAdapter $adapter)
    {
        $this->events()->trigger('send.pre', $message);

        if (true === ($result = $adapter->send($message))) {
            $result = self::MESSAGE_OK;
        }

        $this->events()->trigger('send.post', $message, array('result' => $result));

        if (false === $adapter->getDebug()) {
            $message->setSentAt(new DateTime)
                    ->setResult($result);
        }
    }

    /**
     * Validates and returns the correct adapter based on if a default was set.
     *
     * @param MssMessage\Transport\AbstractAdapter $adapter
     */
    protected function validateAdapter(AbstractAdapter &$adapter = null)
    {
        if (null !== $adapter) {
            ; // intentionall left blank
        } else if (null !== $this->adapter) {
            $adapter = $this->adapter;
        } else {
            throw new RuntimeException(sprintf(
                'no transport adapter specified and default was not set'
            ));
        }
    }

    /**
     * Validates a message using the messages isValid() method.
     *
     * @throws RuntimeException on invalid data
     * @param MssMessage\Message $message
     */
    protected function validateMessage(Message $message)
    {
        if (true !== ($result = $message->isValid())) {
            throw new RuntimeException(sprintf(
                'Errors occured during message validation:%s * %s',
                PHP_EOL,
                implode("\n * ", $result)
            ));
        }
    }
}