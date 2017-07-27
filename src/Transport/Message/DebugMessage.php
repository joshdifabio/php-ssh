<?php
namespace SSH2\Transport\Message;

use SSH2\Transport\Message\Message;
use SSH2\Transport\TransportMessageNumber;

class DebugMessage extends Message
{
    private $alwaysDisplay;
    private $message;
    private $languageTag;

    public function __construct(bool $alwaysDisplay, string $message, string $languageTag)
    {
        $this->alwaysDisplay = $alwaysDisplay;
        $this->message = $message;
        $this->languageTag = $languageTag;

        $binary = \pack('CCNa*Na*',
            TransportMessageNumber::DEBUG,
            $alwaysDisplay,
            \strlen($message),
            $message,
            \strlen($languageTag),
            $languageTag
        );
        parent::__construct(TransportMessageNumber::DEBUG, $binary);
    }

    public function getAlwaysDisplay(): bool
    {
        return $this->alwaysDisplay;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getLanguageTag(): string
    {
        return $this->languageTag;
    }
}
