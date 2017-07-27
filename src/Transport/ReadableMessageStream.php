<?php
namespace SSH2\Transport;

use SSH2\Future;
use SSH2\Transport\Message\Message;

interface ReadableMessageStream
{
    /**
     * @return null|Message
     */
    public function read();

    public function awaitMessage(): Future;

    public function awaitReadable(): Future;
}
