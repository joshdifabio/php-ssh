<?php
namespace SSH2\Connection;

use SSH2\Connection\Internal\InboundChannelOpenMessageManager;
use SSH2\Connection\Internal\Message\ChannelOpenMessage;

class ChannelOpenRequestHandle
{
    public function getChannelType(): string
    {
        return $this->channelOpenMessage->getChannelType();
    }

    public function getRequest(): ChannelOpenRequest
    {
        return ChannelOpenRequest::ofType($this->getChannelType())
            ->withChannelOptions($this->channelOpenMessage->getSenderConfiguration());
    }

    public function confirm(ChannelConfirmation $confirmation = null): Channel
    {
        if (!$this->pending) {
            throw new \Exception();
        }

        $this->pending = false;
        return $this->channelManager->confirm(
            $this->channelOpenMessage->getSenderChannel(),
            $confirmation ?: ChannelConfirmation::create()
        );
    }

    public function fail(ChannelFailureReason $reason)
    {
        if (!$this->pending) {
            throw new \Exception();
        }

        $this->pending = false;
        $this->channelManager->fail($this->channelOpenMessage->getSenderChannel(), $reason);
    }

    // internal

    private $channelManager;
    private $channelOpenMessage;
    private $pending = true;

    /**
     * @internal
     */
    public function __construct(
        InboundChannelOpenMessageManager $channelManager,
        ChannelOpenMessage $channelOpenMessage
    ) {
        $this->channelManager = $channelManager;
        $this->channelOpenMessage = $channelOpenMessage;
    }

    /**
     * @internal
     */
    public function __destruct()
    {
        if ($this->pending) {
            $this->fail(ChannelFailureReason::unknownChannelType());
        }
    }
}
