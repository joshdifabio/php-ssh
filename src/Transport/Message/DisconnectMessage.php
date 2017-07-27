<?php
namespace SSH2\Transport\Message;

use SSH2\Transport\TransportMessageNumber;

class DisconnectMessage extends Message
{
    private $reasonCode;
    private $description;
    private $languageTag;

    public function __construct(int $reasonCode, string $description, string $languageTag)
    {
        $this->reasonCode = $reasonCode;
        $this->description = $description;
        $this->languageTag = $languageTag;

        $binary = \pack('CNNa*Na*',
            TransportMessageNumber::DISCONNECT,
            $reasonCode,
            \strlen($description),
            $description,
            \strlen($languageTag),
            $languageTag
        );
        parent::__construct(TransportMessageNumber::DISCONNECT, $binary);
    }

    public function getReasonCode(): int
    {
        return $this->reasonCode;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getLanguageTag(): string
    {
        return $this->languageTag;
    }
}
