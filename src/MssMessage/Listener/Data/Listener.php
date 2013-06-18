<?php
namespace MssMessage\Listener\Data;
use Zend\EventManager\Event;

interface Listener 
{
    public function contactType(Event $e);
    public function messageType(Event $e);
    public function sender(Event $e);
    public function senderName(Event $e);
    public function recipients(Event $e);
    public function preMessageCreate(Event $e);
    public function postMessageCreate(Event $e);
}
