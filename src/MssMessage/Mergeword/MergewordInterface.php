<?php

namespace MssMessage\Mergeword;

use MssMessage\Model\MessageRecipientClient;

interface MergewordInterface
{
    public function getReplacementsFor(MessageRecipientClient $mrc);
}
