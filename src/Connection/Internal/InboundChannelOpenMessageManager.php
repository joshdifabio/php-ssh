<?php
namespace SSH2\Connection\Internal;

use SSH2\Connection\Channel;
use SSH2\Connection\ChannelOptions;
use SSH2\Connection\ChannelConfirmation;
use SSH2\Connection\ChannelFailureReason;
use SSH2\Connection\Internal\Message\ConnectionMessageNumber;
use SSH2\Connection\Internal\Message\ChannelOpenConfirmationMessage;
use SSH2\Connection\Internal\Message\ChannelOpenFailureMessage;
use SSH2\Connection\Internal\Message\ChannelOpenMessage;
use SSH2\Connection\ChannelOpenRequestHandle;
use SSH2\Internal\Observers;
use SSH2\MessageProtocol;
use SSH2\Subscription;

/**
 * @internal
 */
class InboundChannelOpenMessageManager
{
    private $underlyingProtocol;
    private $messageRouter;
    private $channels;
    private $channelOpenObservers;
    /** @var ChannelOpenMessage[] */
    private $pendingChannelOpenMessages = [];
    private $connectionClosed = false;

    public function __construct(
        MessageProtocol $underlyingProtocol,
        ChannelMessageRouter $messageRouter,
        ChannelSet $channels
    ) {
        $this->underlyingProtocol = $underlyingProtocol;
        $this->messageRouter = $messageRouter;
        $this->channels = $channels;
        $this->channelOpenObservers = new Observers();
        $this->initObservers();
    }

    public function onChannelOpen(callable $observer): Subscription
    {
        if ($this->connectionClosed) {
            return new Subscription(function () {});
        }

        return $this->channelOpenObservers->add($observer);
    }

    public function confirm(int $remoteChannelId, ChannelConfirmation $confirmation): Channel
    {
        if (!isset($this->pendingChannelOpenMessages[$remoteChannelId])) {
            throw new \Error();
        }

        $channelOpenMessage = $this->pendingChannelOpenMessages[$remoteChannelId];
        unset($this->pendingChannelOpenMessages[$remoteChannelId]);
        $localChannelId = $this->channels->nextLocalChannelId();
        $confirmationMessage = new ChannelOpenConfirmationMessage($remoteChannelId, $localChannelId, $confirmation);
        $this->underlyingProtocol->send($confirmationMessage);
        $channelMessageProtocol = new ChannelMessageProtocol($this->underlyingProtocol, $this->messageRouter, $localChannelId);
        try {
            $channel = new Channel($channelMessageProtocol, $confirmationMessage, $channelOpenMessage);
        } catch (\Throwable $e) {
            die($e);
        }
        $this->channels->add($localChannelId, $channel);
        return $channel;
    }

    public function fail(int $remoteChannelId, ChannelFailureReason $reason)
    {
        if (!isset($this->pendingChannelOpenMessages[$remoteChannelId])) {
            throw new \Error();
        }

        unset($this->pendingChannelOpenMessages[$remoteChannelId]);
        $failureMessage = new ChannelOpenFailureMessage($remoteChannelId, $reason);
        $this->underlyingProtocol->send($failureMessage);
    }

    private function initObservers()
    {
        $this->underlyingProtocol->onMessageReceived(
            ConnectionMessageNumber::CHANNEL_OPEN,
            function (ChannelOpenMessage $message) {
                $remoteChannelId = $message->getSenderChannel();
                $request = new ChannelOpenRequestHandle($this, $message);
                $this->pendingChannelOpenMessages[$remoteChannelId] = $message;
                $this->channelOpenObservers->fire($request);
            }
        );

        $this->underlyingProtocol->whenConnectionClosed()->then(function () {
            $this->connectionClosed = true;
            $this->channelOpenObservers->cancelAll();
        });
    }
}
