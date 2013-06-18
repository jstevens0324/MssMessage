<?php

namespace MssMessage\Listener;

use Zend\EventManager\Event;

interface ListenerInterface
{
    public function queuePre(Event $e);
    
    public function queuePost(Event $e);
    
    public function sendPre(Event $e);
    
    public function sendPost(Event $e);
}