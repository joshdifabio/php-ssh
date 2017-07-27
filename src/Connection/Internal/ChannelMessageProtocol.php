<?php
namespace SSH2\Connection\Internal;

use SSH2\Connection\Internal\Message\ConnectionMessageNumber;
use SSH2\Internal\Coroutine;
use SSH2\Promise;
use SSH2\Message;
use SSH2\MessageProtocol;
use SSH2\Subscription;
use SSH2\SubscriptionCollection;

/**
 * @internal
 */
class ChannelMessageProtocol
{
    private $underlyingProtocol;
    private $messageRouter;
    private $localChannelId;
    private $writeEnded = false;
    private $readEnded = false;
    /** @var null|Promise */
    private $waitPromise;
    /** @var SubscriptionCollection */
    private $subscriptions;
    private $writeEndPromise;
    private $readEndPromise;

    public function __construct(
        MessageProtocol $underlyingProtocol,
        ChannelMessageRouter $messageRouter,
        int $localChannelId
    ) {
        $this->underlyingProtocol = $underlyingProtocol;
        $this->messageRouter = $messageRouter;
        $this->localChannelId = $localChannelId;
        $this->writeEndPromise = new Promise();
        $this->readEndPromise = new Promise();
        $this->subscriptions = new SubscriptionCollection();
        $this->initObservers();
    }

    public function send(Message $message): int
    {
        if ($this->writeEnded) {
            return MessageProtocol::ENDED;
        }

        if ($message->getMessageNumber() === ConnectionMessageNumber::CHANNEL_CLOSE) {
            $this->endWrite();
            $this->underlyingProtocol->send($message);
            return MessageProtocol::ENDED;
        }

        return $this->underlyingProtocol->send($message);
    }

    public function wait(): Promise
    {
        if (isset($this->waitPromise)) {
            return $this->waitPromise;
        }

        $this->waitPromise = $waitPromise = Coroutine::run(function () {
            $protocolState = yield $this->underlyingProtocol->wait();
            if ($protocolState === MessageProtocol::ENDED || $this->writeEnded) {
                return MessageProtocol::ENDED;
            }
            return MessageProtocol::READY;
        });

        $waitPromise->then(function ($state) use ($waitPromise) {
            if ($state === MessageProtocol::READY && $this->waitPromise === $waitPromise) {
                $this->waitPromise = null;
            }
        });

        return $waitPromise;
    }

    public function onGlobalMessageReceived(int $messageNumber, callable $observer): Subscription
    {
        if ($this->readEnded) {
            return new Subscription(function () {});
        }

        $subscription = $this->underlyingProtocol->onMessageReceived($messageNumber, $observer);
        $this->subscriptions->add($subscription);
        return $subscription;
    }

    public function onChannelMessageReceived(int $messageNumber, callable $observer): Subscription
    {
        if ($this->readEnded) {
            return new Subscription(function () {});
        }

        $subscription = $this->messageRouter->onMessageReceived($this->localChannelId, $messageNumber, $observer);
        $this->subscriptions->add($subscription);
        return $subscription;
    }

    public function whenReceiveEnded(): Promise
    {
        return $this->readEndPromise;
    }

    public function whenSendEnded(): Promise
    {
        return $this->writeEndPromise;
    }

    public function whenConnectionClosed(): Promise
    {
        return $this->underlyingProtocol->whenConnectionClosed();
    }

    private function initObservers()
    {
        $this->underlyingProtocol->whenConnectionClosed()->then(function () {
            $this->endRead();
            $this->endWrite();
        });

        $subscription = $this->onChannelMessageReceived(ConnectionMessageNumber::CHANNEL_CLOSE, function () {
            $this->endRead();
        });
        $this->subscriptions->add($subscription);
    }

    private function endRead()
    {
        if ($this->readEnded) {
            return;
        }

        $this->readEnded = true;
        $this->subscriptions->cancelAll();
        $this->readEndPromise->resolve();
    }

    private function endWrite()
    {
        if ($this->writeEnded) {
            return;
        }

        $this->writeEnded = true;
        if (!isset($this->waitPromise)) {
            $this->waitPromise = new Promise();
        }
        $this->waitPromise->resolve(MessageProtocol::ENDED);
        $this->writeEndPromise->resolve();
    }
}
