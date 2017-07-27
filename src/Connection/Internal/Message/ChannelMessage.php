<?php
namespace SSH2\Connection\Internal\Message;

interface ChannelMessage
{
    public function getRecipientChannel(): int;
}
