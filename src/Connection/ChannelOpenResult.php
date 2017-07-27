<?php
namespace SSH2\Connection;

class ChannelOpenResult
{
    const SUCCESS = 1;
    const FAILURE = 2;
    const DISCONNECT = 3;

    public function getType(): int
    {
        return isset($this->channel);
    }

    public function getChannel(): ?Channel
    {
        return $this->channel;
    }

    public function getFailureReason(): ?ChannelFailureReason
    {
        return $this->failureReason;
    }

    // internal

    private $type;
    private $channel;
    private $failureReason;

    /**
     * @internal
     */
    public static function success(Channel $channel): ChannelOpenResult
    {
        $result = new ChannelOpenResult();
        $result->type = self::SUCCESS;
        $result->channel = $channel;
        return $result;
    }

    /**
     * @internal
     */
    public static function failure(ChannelFailureReason $reason): ChannelOpenResult
    {
        $result = new ChannelOpenResult();
        $result->type = self::FAILURE;
        $result->failureReason = $reason;
        return $result;
    }

    /**
     * @internal
     */
    public static function disconnect(): ChannelOpenResult
    {
        $result = new ChannelOpenResult();
        $result->type = self::DISCONNECT;
        return $result;
    }

    private function __construct()
    {

    }
}
