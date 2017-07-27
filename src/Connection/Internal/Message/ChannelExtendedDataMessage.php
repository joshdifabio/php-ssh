<?php
namespace SSH2\Connection\Internal\Message;

use SSH2\Message;

class ChannelExtendedDataMessage extends Message implements ChannelMessage
{
    private $recipientChannel;
    private $dataTypeCode;
    private $data;

    public function __construct(int $recipientChannel, int $dataTypeCode, string $data)
    {
        $this->recipientChannel = $recipientChannel;
        $this->dataTypeCode = $dataTypeCode;
        $this->data = $data;

        $binary = \pack('CNNa*', ConnectionMessageNumber::CHANNEL_EXTENDED_DATA, $dataTypeCode, \strlen($data), $data);
        parent::__construct(ConnectionMessageNumber::CHANNEL_EXTENDED_DATA, $binary);
    }

    public function getRecipientChannel(): int
    {
        return $this->recipientChannel;
    }

    public function getDataTypeCode(): int
    {
        return $this->dataTypeCode;
    }

    public function getData(): string
    {
        return $this->data;
    }
}
