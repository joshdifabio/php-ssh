<?php
namespace SSH2\Transport;

use SSH2\Coroutine_;
use SSH2\Transport\KeyExchange\ClientSideKeyExchange;
use SSH2\Transport\Message\Message;
use SSH2\Transport\Packet\InboundDataStream;
use SSH2\Transport\Packet\OutboundDataStream;

class ClientSideTransportProtocol
{
    public static function start(ClientConfiguration $config): ClientSideTransportProtocol
    {
        return new ClientSideTransportProtocol($config);
    }

    public function sendMessage(Message $message): int
    {
        if ($this->hasDisconnected) {
            throw new \Exception('Cannot send messages as we have disconnected.');
        }

        switch ($messageNumber = $message->getMessageNumber()) {

        }

        $sequenceNumber = $this->outboundStream->writePacket($message->getPayload());

        if ($messageNumber === TransportMessageNumber::DISCONNECT) {
            $this->hasDisconnected = true;
        }

        return $sequenceNumber;
    }

    public function setInboundMessageHandler(int $minMessageNo, int $maxMessageNo, callable $handler)
    {
        if ($minMessageNo > $maxMessageNo || $minMessageNo <= 0) {
            throw new \Exception;
        }

        $handlers = $this->receivedMessageHandlers;

        for ($messageNo = $minMessageNo; $messageNo <= $maxMessageNo; $messageNo++) {
            if (isset($handlers[$messageNo])) {
                throw new \Exception;
            }
            $handlers[$messageNo] = $handler;
        }

        $this->receivedMessageHandlers = $handlers;
    }

    public function getSessionIdentifier(): string
    {

    }

    public function providesConfidentiality(): bool
    {

    }

    public function getInboundDataBuffer(): WritableDataStream
    {
        return $this->inboundStream;
    }

    public function getOutboundDataBuffer(): ReadableDataStream
    {
        return $this->outboundStream;
    }

    // internals

    private $config;
    private $outboundStream;
    private $inboundStream;
    private $hasDisconnected = false;
    private $receivedMessageHandlers = [];

    private function __construct(ClientConfiguration $config)
    {
        $this->config = $config;
        $this->outboundStream = new OutboundDataStream;
        $this->inboundStream = new InboundDataStream($this->createInboundMessageHandlerFn());
        parent::__construct();
    }

    protected function run(): \Generator
    {
        $identificationString = $this->getIdentificationString();
        $this->outboundStream->writeBinaryData($identificationString);
        $keyExchange = ClientSideKeyExchange::begin($this->config->getKeyExchangeConfiguration());
        $this->setInboundMessageHandler(20, 49, function (Message $receivedMessage) use ($keyExchange) {
            $sentMessages = $keyExchange->send($receivedMessage) ?? [];
            foreach ($sentMessages as $message) {
                $this->sendMessage($message);
            }
        });
        $this->sendIdentificationString($config->getSoftwareVersion(), $config->getIdentificationStringComments());
        foreach ($keyExchange->current() ?? [] as $sentMessage) {
            $this->sendMessage($sentMessage);
        }
    }

    private function getIdentificationString()
    {
        $softwareVersion = $this->config->getSoftwareVersion();
        $comments = $this->config->getIdentificationStringComments();

        if (null === $comments || '' === $comments) {
            return "SSH-2.0-$softwareVersion";
        } else {
            return "SSH-2.0-$softwareVersion $comments";
        }
    }

    private function createInboundMessageHandlerFn(): callable
    {

    }
}
