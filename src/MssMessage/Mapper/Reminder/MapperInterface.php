<?php

namespace MssMessage\Mapper\Reminder;

use MssMessage\Message;

interface MapperInterface
{
    public function saveMessageReminder(Message $message);
}