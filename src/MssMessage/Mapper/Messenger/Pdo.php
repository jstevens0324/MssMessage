<?php

namespace MssMessage\Mapper\Messenger;

use PDO as PhpPdo,
    Zend\Mail\Message;

class Pdo implements MapperInterface
{
    /**
     * @var PDO
     */
    private $pdo;
    
    public function __construct(PhpPdo $pdo) 
    {
        $this->pdo = $pdo;
    }
    
    public function queue(Message $message) 
    {
        
    }
}