<?php

namespace MssMessage\Transport;

use InvalidArgumentException,
    MssMessage\Message;

class File extends AbstractAdapter
{
    /**
     * @var resource
     */
    private $fh;
    
    /**
     * @var int
     */
    private $start;
    
    public function __construct($path = null, $prefix)
    {
        $this->start = microtime(true);
        
        if (null === $path) {
            throw new InvalidArgumentException(sprintf(
                'file adapter requires a filepath'
            ));
        }
        
        if (!file_exists($path)) {
            throw new InvalidArgumentException(sprintf(
                'path "%s" could not be found',
                $path
            ));
        }
        
        if (!is_writable($path)) {
            throw new InvalidArgumentException(sprintf(
                'path "%s" is not writeable',
                $path
            ));
        }
        
        $filename = sprintf('%s-%s-%s.out', $prefix, date('Y-m-d'), time());
        $this->fh = fopen(sprintf('%s/%s', $path, $filename), 'w');
        
        $this->writeLn(sprintf('message processing started at %s', date('h:i:sa', $this->start)));
    }
    
    public function __destruct()
    {
        if (is_resource($this->fh)) {
            $end = microtime(true);
            
            $this->writeLn(sprintf('message processing ended at %s', date('h:i:sa', $end)));
            $this->writeLn(sprintf('processing took %2.5f seconds', $end - $this->start));
            
            fclose($this->fh);
        }
    }
    
    public function send(Message $message)
    {
        $this->writeLn();
        $this->writeLn('============================================================');
        $this->writeLn(sprintf(' Queued: %s', $message->getQueuedAt()->format('Y-m-d H:I:s')));
        $this->writeLn(sprintf('Subject: %s', $message->getSubject()));
        $this->writeLn(sprintf('   From: %s <%s>', $message->getSenderName(), $message->getSender()));
        
        $recipient = $message->getRecipient();
        $result    = AbstractAdapter::RESULT_OK;
        
        if (($contact = $this->getContactAddress($message))) {
            $this->writeLn(sprintf(
                '     To: %s %s <%s>',
                $recipient->getFirstName(),
                $recipient->getLastName(),
                $contact
            ));
            
            $this->writeLn();
            $this->writeLn($message->getBody());
            $this->writeLn();
        } else {
            $result = AbstractAdapter::RESULT_NO_CONTACT_DATA;
            
            $this->writeLn(sprintf(
                'skipping recipient %s %s because no contact information could be found',
                $recipient->getFirstName(),
                $recipient->getLastName()
            ));
        }
        
        return $result;
    }
    
    protected function write($msg)
    {
        fwrite($this->fh, $msg);
    }
    
    protected function writeLn($msg = '')
    {
        $this->write($msg . "\n");
    }
}