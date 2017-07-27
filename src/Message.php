<?php
namespace SSH2;

class Message
{
    private $messageNumber;
    private $payload;

    public function __construct(int $messageNumber, string $payload)
    {
        $this->messageNumber = $messageNumber;
        $this->payload = $payload;
    }

    public function getMessageNumber(): int
    {
        return $this->messageNumber;
    }

    public function getPayload(): string
    {
        return $this->payload;
    }
}
