<?php
namespace SSH2\Tests;

use SSH2\Internal\MessageBus;
use SSH2\Message;
use SSH2\MessageProtocol;
use SSH2\Promise;
use SSH2\Subscription;

class DuplexMessageProtocol
{
    private $server;
    private $client;

    public function __construct()
    {
        $serverSideMessageBus = new MessageBus();
        $clientSideMessageBus = new MessageBus();
        $this->server = $this->createMessageProtocol($serverSideMessageBus, $clientSideMessageBus);
        $this->client = $this->createMessageProtocol($clientSideMessageBus, $serverSideMessageBus);
    }

    public function getServer(): MessageProtocol
    {
        return $this->server;
    }

    public function getClient(): MessageProtocol
    {
        return $this->client;
    }

    private function createMessageProtocol(MessageBus $localMessageBus, MessageBus $remoteMessageBus)
    {
        return new class ($localMessageBus, $remoteMessageBus) implements MessageProtocol {
            private $localMessageBus;
            private $remoteMessageBus;

            public function __construct(MessageBus $localMessageBus, MessageBus $remoteMessageBus)
            {
                $this->localMessageBus = $localMessageBus;
                $this->remoteMessageBus = $remoteMessageBus;
            }

            public function send(Message $message): int
            {
                $this->remoteMessageBus->handleMessage($message);
                return MessageProtocol::READY;
            }

            public function wait(): Promise
            {
                $promise = new Promise();
                $promise->resolve(MessageProtocol::READY);
                return $promise;
            }

            public function onMessageReceived(int $messageNumber, callable $observer): Subscription
            {
                return $this->localMessageBus->addHandler($messageNumber, $observer);
            }

            public function whenConnectionClosed(): Promise
            {
                return new Promise();
            }
        };
    }
}
