<?php
namespace SSH2\Connection;

use SSH2\Transport\LocalizedString;

class ChannelFailureReason
{
    const ADMINISTRATIVELY_PROHIBITED = 1;
    const CONNECT_FAILED = 2;
    const UNKNOWN_CHANNEL_TYPE = 3;
    const RESOURCE_SHORTAGE = 4;

    public static function withReasonCode(int $reasonCode)
    {
        return new ChannelFailureReason($reasonCode);
    }

    public static function unknownChannelType()
    {
        return self::withReasonCode(self::UNKNOWN_CHANNEL_TYPE);
    }

    public function getReasonCode(): int
    {
        return $this->reasonCode;
    }

    public function withDescription(LocalizedString $description)
    {
        $clone = clone $this;
        $clone->description = $description;
        return $clone;
    }

    public function getDescription(): ?LocalizedString
    {
        return $this->description;
    }

    private $reasonCode;
    private $description;

    private function __construct(int $reasonCode)
    {
        $this->reasonCode = $reasonCode;
    }
}
