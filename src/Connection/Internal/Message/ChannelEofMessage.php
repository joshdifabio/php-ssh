<?php
namespace SSH2\Connection\Internal\Message;

use SSH2\Message;

class ChannelEofMessage extends Message implements ChannelMessage
{
    private $recipientChannel;

    public function __construct(int $recipientChannel)
    {
        $this->recipientChannel = $recipientChannel;

        $binary = \pack('CN', ConnectionMessageNumber::CHANNEL_EOF, $recipientChannel);
        parent::__construct(ConnectionMessageNumber::CHANNEL_EOF, $binary);
    }

    public function getRecipientChannel(): int
    {
        return $this->recipientChannel;
    }
}
