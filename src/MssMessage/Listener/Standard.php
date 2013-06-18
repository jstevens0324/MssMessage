<?php

namespace MssMessage\Listener;

use Zend\EventManager\Event,
    Zend\EventManager\EventCollection,
    Zend\EventManager\ListenerAggregate;

class Standard implements ListenerAggregate, ListenerInterface 
{
    protected static $filter;

    public function attach(EventCollection $events)
    {
        $events->attach('queue.pre', array($this, 'queuePre'));
        $events->attach('queue.post', array($this, 'queuePost'));
        $events->attach('send.pre', array($this, 'sendPre'));
        $events->attach('send.post', array($this, 'sendPost'));
    }

    public function detach(EventCollection $events)
    {
    }
    
    public function queuePre(Event $e)
    {
    }
    
    public function queuePost(Event $e)
    {
    }
    
    public function sendPre(Event $e)
    {
    }
    
    public function sendPost(Event $e)
    {
    }
}
