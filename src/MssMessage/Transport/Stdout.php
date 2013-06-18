<?php

namespace MssMessage\Transport;

class Stdout extends File
{
    public function __construct()
    {
        $this->start = microtime(true);
        $this->writeLn(sprintf('message processing started at %s', date('h:i:sa', $this->start)));
    }
    
    public function __destruct()
    {
        $end = microtime(true);
        
        $this->writeLn(sprintf('message processing ended at %s', date('h:i:sa', $end)));
        $this->writeLn(sprintf('processing took %2.5f seconds', $end - $this->start));
    }
    
    protected function write($msg)
    {
        echo $msg;
    }
    
    protected function writeLn($msg = '')
    {
        $this->write($msg . "\n");
    }
}