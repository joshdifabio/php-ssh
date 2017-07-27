<?php
namespace SSH2\Transport\KeyExchange;

use SSH2\Transport\Message\Message;

class KexinitMessage extends Message
{
    use KexinitMessageTrait;

    public function getCookie(): string
    {

    }
}
