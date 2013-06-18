<?php
namespace MssMessage\Listener\Queue;
use Zend\EventManager\Event;

interface Listener 
{
    public function queue(Event $e);
}
