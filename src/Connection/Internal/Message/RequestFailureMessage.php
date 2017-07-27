<?php
namespace SSH2\Connection\Internal\Message;

use SSH2\Message;

class RequestFailureMessage extends Message
{
    public function __construct()
    {
        $binary = \pack('C', ConnectionMessageNumber::REQUEST_FAILURE);
        parent::__construct(ConnectionMessageNumber::REQUEST_FAILURE, $binary);
    }
}
