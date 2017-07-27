<?php
namespace SSH2\Connection\Internal\Message;

use SSH2\Message;

class GlobalRequestMessage extends Message
{
    private $requestName;
    private $wantReply;
    private $requestSpecificData;

    public function __construct(string $requestName, bool $wantReply, string $requestSpecificData)
    {
        $this->requestName = $requestName;
        $this->wantReply = $wantReply;
        $this->requestSpecificData = $requestSpecificData;

        $binary = \pack(
            'CNa*C',
            ConnectionMessageNumber::GLOBAL_REQUEST,
            \strlen($requestName),
            $requestName,
            $wantReply
        ) . $requestSpecificData;
        parent::__construct(ConnectionMessageNumber::GLOBAL_REQUEST, $binary);
    }

    public function getRequestName(): string
    {
        return $this->requestName;
    }

    public function getWantReply(): bool
    {
        return $this->wantReply;
    }

    public function getRequestSpecificData(): string
    {
        return $this->requestSpecificData;
    }
}
