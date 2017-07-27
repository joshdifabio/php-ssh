<?php
namespace SSH2\Connection\Internal\Message;

use SSH2\Message;

class ChannelFailureMessage extends Message implements ChannelMessage
{
    private $recipientChannel;

    public function __construct(int $recipientChannel)
    {
        $this->recipientChannel = $recipientChannel;

        $binary = \pack('CN', ConnectionMessageNumber::CHANNEL_FAILURE, $recipientChannel);
        parent::__construct(ConnectionMessageNumber::CHANNEL_FAILURE, $binary);
    }

    public function getRecipientChannel(): int
    {
        return $this->recipientChannel;
    }
}
