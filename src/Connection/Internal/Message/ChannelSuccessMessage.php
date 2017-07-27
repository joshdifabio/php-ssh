<?php
namespace SSH2\Connection\Internal\Message;

use SSH2\Message;

class ChannelSuccessMessage extends Message implements ChannelMessage
{
    private $recipientChannel;

    public function __construct(int $recipientChannel)
    {
        $this->recipientChannel = $recipientChannel;

        $binary = \pack('CN', ConnectionMessageNumber::CHANNEL_SUCCESS, $recipientChannel);
        parent::__construct(ConnectionMessageNumber::CHANNEL_SUCCESS, $binary);
    }

    public function getRecipientChannel(): int
    {
        return $this->recipientChannel;
    }
}
