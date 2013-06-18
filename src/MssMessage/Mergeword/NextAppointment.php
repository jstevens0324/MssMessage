<?php

namespace MssMessage\Decorator;

use MssMessage\Model\MessageRecipientClient;

class NextAppointment implements DecoratorInterface
{
    public function getReplacementsFor(MessageRecipientClient $mrc);
}
