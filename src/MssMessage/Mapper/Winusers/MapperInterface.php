<?php

namespace MssMessage\Mapper\Winusers;

use MssMessage\Message;

interface MapperInterface
{
    public function getPatientList($dsid, array $patients);
}