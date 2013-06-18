<?php

namespace MssMessage\Mapper\Messenger;

use DateTime,
    Exception,
    MssMessage\Model\MergewordSet,
    MssMessage\Model\Message,
    MssMessage\Model\MessageRecipientClient,
    MssMessage\Service\Messenger;

class File implements MapperInterface
{
    /**
     * @var resource
     */
    private $fh;
    
    public function __construct($path = null, $prefix)
    {
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
    }
    
    public function __destruct()
    {
        if (is_resource($this->fh)) {
            fclose($this->fh);
        }
    }
    
    public function findById($messageId)
    {
        
    }
    
    public function findBatch($batch, DateTime $timestamp = null)
    {
        
    }
    
    public function queue(Message $message)
    {
        fwrite($this->fh, var_export($message, true));
    }
    
    public function queueBatch(array $messages)
    {
        foreach($messages as $message) {
            $this->queue($message);
        }
    }
    
    public function save(Message $message)
    {
        
    }
    
    public function saveBatch(array $messages)
    {
        
    }
    
    public function getCompanyMergewordSet($companyId)
    {
        $set = new MergewordSet;
        $set->setId('1')
            ->setName('FileMapper Mergeword Set')
            ->setPrefix('<')
            ->setSuffix('>');
            
        $set->addAlias('clientFirstName', 'first-name')
            ->addAlias('clientLastName', 'last-name');
            
        return $set;
    }
}