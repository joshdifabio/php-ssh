<?php
namespace SSH2\Transport\Message;

abstract class Message
{
    private $messageNumber;
    private $payload;
    private $sequenceNumber;

    public function __construct(int $messageNumber, string $payload)
    {
        $this->messageNumber = $messageNumber;
        $this->payload = $payload;
    }

    public function getMessageNumber(): int
    {
        return $this->messageNumber;
    }

    /**
     * @return null|int
     */
    public function getSequenceNumber()
    {
        return $this->sequenceNumber;
    }

    public function getPayload(): string
    {
        return $this->payload;
    }
}
