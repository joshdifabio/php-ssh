<?php
namespace SSH2\Connection\Internal\Message;

use SSH2\Message;

class ChannelRequestMessage extends Message implements ChannelMessage
{
    private $recipientChannel;
    private $requestType;
    private $wantReply;
    private $typeSpecificData;

    public function __construct(int $recipientChannel, string $requestType, bool $wantReply, string $typeSpecificData)
    {
        $this->recipientChannel = $recipientChannel;
        $this->requestType = $requestType;
        $this->wantReply = $wantReply;
        $this->typeSpecificData = $typeSpecificData;

        $binary = \pack(
            'CNNa*C',
            ConnectionMessageNumber::CHANNEL_REQUEST,
            $recipientChannel,
            \strlen($requestType),
            $requestType,
            $wantReply
        ) . $typeSpecificData;
        parent::__construct(ConnectionMessageNumber::CHANNEL_REQUEST, $binary);
    }

    public function getRecipientChannel(): int
    {
        return $this->recipientChannel;
    }

    public function getRequestType(): string
    {
        return $this->requestType;
    }

    public function getWantReply(): bool
    {
        return $this->wantReply;
    }

    public function getTypeSpecificData(): string
    {
        return $this->typeSpecificData;
    }
}
