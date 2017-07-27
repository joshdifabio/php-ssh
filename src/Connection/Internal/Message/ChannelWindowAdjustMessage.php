<?php
namespace SSH2\Connection\Internal\Message;

use SSH2\Message;

class ChannelWindowAdjustMessage extends Message implements ChannelMessage
{
    private $recipientChannel;
    private $bytesToAdd;

    public function __construct(int $recipientChannel, int $bytesToAdd)
    {
        $this->recipientChannel = $recipientChannel;
        $this->bytesToAdd = $bytesToAdd;

        $binary = \pack('CNN', ConnectionMessageNumber::CHANNEL_WINDOW_ADJUST, $recipientChannel, $bytesToAdd);
        parent::__construct(ConnectionMessageNumber::CHANNEL_WINDOW_ADJUST, $binary);
    }

    public function getRecipientChannel(): int
    {
        return $this->recipientChannel;
    }

    public function getBytesToAdd(): int
    {
        return $this->bytesToAdd;
    }
}
