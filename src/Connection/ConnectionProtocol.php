<?php
namespace SSH2\Connection;

use SSH2\Connection\Internal\ChannelMessageRouter;
use SSH2\Connection\Internal\ChannelSet;
use SSH2\Connection\Internal\InboundChannelOpenMessageManager;
use SSH2\Connection\Internal\InboundGlobalRequestManager;
use SSH2\Connection\Internal\OutboundChannelOpenMessageManager;
use SSH2\Connection\Internal\OutboundGlobalRequestManager;
use SSH2\Connection\Internal\Message\GlobalRequestMessage;
use SSH2\Promise;
use SSH2\MessageProtocol;
use SSH2\Subscription;

class ConnectionProtocol
{
    private $underlyingProtocol;
    private $outboundChannelManager;
    private $inboundChannelManager;
    private $outboundGlobalRequestManager;
    private $inboundGlobalRequestManager;

    public function __construct(MessageProtocol $underlyingProtocol)
    {
        $this->underlyingProtocol = $underlyingProtocol;
        $messageRouter = new ChannelMessageRouter($underlyingProtocol);
        $channels = new ChannelSet();
        $this->outboundChannelManager = new OutboundChannelOpenMessageManager($underlyingProtocol, $messageRouter, $channels);
        $this->inboundChannelManager = new InboundChannelOpenMessageManager($underlyingProtocol, $messageRouter, $channels);
        $this->outboundGlobalRequestManager = new OutboundGlobalRequestManager($underlyingProtocol);
        $this->inboundGlobalRequestManager = new InboundGlobalRequestManager($underlyingProtocol);
    }

    /**
     * @see ChannelOpenResult
     */
    public function openChannel(ChannelOpenRequest $channelOpenRequest): Promise
    {
        $promise = new Promise();
        $this->outboundChannelManager->openChannel($channelOpenRequest, [$promise, 'resolve']);
        return $promise;
    }

    /**
     * @see ChannelOpenRequestHandle
     */
    public function onChannelOpenRequestReceived(callable $observer): Subscription
    {
        return $this->inboundChannelManager->onChannelOpen($observer);
    }

    /**
     * @see GlobalRequestResult
     */
    public function sendGlobalRequest(string $requestName, string $data): Promise
    {
        $promise = new Promise();
        $this->outboundGlobalRequestManager->sendRequest($requestName, $data, [$promise, 'resolve']);
        return $promise;
    }

    /**
     * @see GlobalRequestResult
     */
    public function sendGlobalRequestAndIgnoreReply(string $requestName, string $data)
    {
        $this->underlyingProtocol->send(new GlobalRequestMessage($requestName, false, $data));
    }

    /**
     * @see GlobalRequestHandle
     */
    public function onGlobalRequestReceived(callable $observer): Subscription
    {
        return $this->inboundGlobalRequestManager->onRequest($observer);
    }
}
