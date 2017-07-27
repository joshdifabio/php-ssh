<?php
namespace SSH2\Connection\Internal\Message;

use SSH2\Message;

class ChannelDataMessage extends Message implements ChannelMessage
{
    private $recipientChannel;
    private $data;

    public function __construct(int $recipientChannel, string $data)
    {
        $this->recipientChannel = $recipientChannel;
        $this->data = $data;

        $binary = \pack('CNa*', ConnectionMessageNumber::CHANNEL_DATA, \strlen($data), $data);
        parent::__construct(ConnectionMessageNumber::CHANNEL_DATA, $binary);
    }

    public function getRecipientChannel(): int
    {
        return $this->recipientChannel;
    }

    public function getData(): string
    {
        return $this->data;
    }
}
