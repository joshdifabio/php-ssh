<?php
namespace SSH2\Transport;

use SSH2\Transport\Message\Message;

interface WritableMessageStream
{
    public function write(Message $message);
}
