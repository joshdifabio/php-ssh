<?php
namespace SSH2\Transport\KeyExchange;

use SSH2\Transport\Message\Message;
use SSH2\Transport\TransportMessageNumber;

class NewkeysMessage extends Message
{
    public function __construct()
    {
        parent::__construct(TransportMessageNumber::NEWKEYS, \pack('C', TransportMessageNumber::NEWKEYS));
    }
}
