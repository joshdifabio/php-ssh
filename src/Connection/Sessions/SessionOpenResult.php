<?php
namespace SSH2\Connection\Sessions;

use SSH2\Connection\ChannelFailureReason;

class SessionOpenResult
{
    const SUCCESS = 1;
    const FAILURE = 2;
    const DISCONNECT = 3;

    public function getType(): int
    {
        return $this->type;
    }

    public function getSession(): ?Session
    {
        return $this->session;
    }

    public function getFailureReason(): ?ChannelFailureReason
    {
        return $this->failureReason;
    }

    // internal

    private $type;
    private $session;
    private $failureReason;

    /**
     * @internal
     */
    public static function success(Session $session): SessionOpenResult
    {
        $result = new SessionOpenResult();
        $result->type = self::SUCCESS;
        $result->session = $session;
        return $result;
    }

    /**
     * @internal
     */
    public static function failure(): SessionOpenResult
    {
        $result = new SessionOpenResult();
        $result->type = self::FAILURE;
        return $result;
    }

    /**
     * @internal
     */
    public static function disconnect(): SessionOpenResult
    {
        $result = new SessionOpenResult();
        $result->type = self::DISCONNECT;
        return $result;
    }

    private function __construct()
    {

    }
}
