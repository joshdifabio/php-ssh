<?php
namespace SSH2\Transport;

use SSH2\Transport\Message\Message;

interface ReceivedMessageHandler
{
    public function handleReceivedMessage(int $messageSequenceNumber, Message $message);
}
