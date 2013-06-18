<?php

namespace MssMessage\Mapper\Appointment;

use MssMessage\Message;

interface MapperInterface
{
    public function saveMessageAppointment(Message $message);
}