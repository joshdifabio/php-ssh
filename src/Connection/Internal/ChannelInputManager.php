<?php
namespace SSH2\Connection\Internal;

use SSH2\Connection\Internal\Message\ChannelDataMessage;
use SSH2\Connection\Internal\Message\ChannelEofMessage;
use SSH2\Connection\Internal\Message\ChannelExtendedDataMessage;
use SSH2\Connection\Internal\Message\ChannelWindowAdjustMessage;
use SSH2\Connection\Internal\Message\ConnectionMessageNumber;
use SSH2\Internal\Coroutine;
use SSH2\MessageProtocol;
use SSH2\Promise;

class ChannelInputManager
{
    const WRITABLE = 1;
    const BLOCKED = 2;
    const ENDED = 3;

    public $effectiveMaxPacketSize;

    private $messageProtocol;
    private $remoteChannelId;
    private $remainingWindowSpace;
    private $maxPacketSize;
    /** @var null|Promise */
    private $waitPromise;
    private $endPromise;
    /** @var Promise */
    private $windowAdjustPromise;
    private $ended = false;

    public function __construct(
        ChannelMessageProtocol $messageProtocol,
        int $remoteChannelId,
        int $initialWindowSize,
        int $maximumPacketSize
    ) {
        if ($initialWindowSize < 0 || $maximumPacketSize < 0) {
            throw new \Error();
        }

        $this->messageProtocol = $messageProtocol;
        $this->remoteChannelId = $remoteChannelId;
        $this->remainingWindowSpace = $initialWindowSize;
        $this->maxPacketSize = $maximumPacketSize;
        $this->endPromise = new Promise();
        $this->effectiveMaxPacketSize = \min($this->maxPacketSize, $this->remainingWindowSpace) - 4;

        $subscriber = $this->messageProtocol->onChannelMessageReceived(
            ConnectionMessageNumber::CHANNEL_WINDOW_ADJUST,
            function (ChannelWindowAdjustMessage $message) {
                $this->handleWindowAdjusted($message->getBytesToAdd());
            }
        );

        $this->whenEnded()->then(function () use ($subscriber) {
            $subscriber->cancel();
            $this->effectiveMaxPacketSize = 0;
            if (!isset($this->waitPromise)) {
                $this->waitPromise = new Promise();
            }
            $this->waitPromise->resolve(self::ENDED);
            if (isset($this->windowAdjustPromise)) {
                $this->windowAdjustPromise->resolve();
            }
        });

        $this->messageProtocol->whenSendEnded()->then([$this, 'end']);
    }

    public function sendData(string $data, int $dataTypeCode = null): int
    {
        $dataSize = \strlen($data);

        if ($dataSize > $this->effectiveMaxPacketSize) {
            throw new \Error("Cannot send $dataSize bytes of data as the effective max packet size is only {$this->effectiveMaxPacketSize} bytes.");
        }

        $this->remainingWindowSpace -= ($dataSize + 4);
        $this->effectiveMaxPacketSize = \min($this->maxPacketSize, $this->remainingWindowSpace) - 4;

        if (isset($dataTypeCode)) {
            return $this->messageProtocol->send(new ChannelExtendedDataMessage($this->remoteChannelId, $dataTypeCode, $data));
        }

        return $this->messageProtocol->send(new ChannelDataMessage($this->remoteChannelId, $data));
    }

    public function handleWindowAdjusted(int $adjustment)
    {
        $this->remainingWindowSpace += $adjustment;
        $this->effectiveMaxPacketSize = \min($this->maxPacketSize, $this->remainingWindowSpace) - 4;

        if (isset($this->windowAdjustPromise)) {
            $this->windowAdjustPromise->resolve();
        }
    }

    public function wait(): Promise
    {
        if (isset($this->waitPromise)) {
            return $this->waitPromise;
        }

        $this->waitPromise = $waitPromise = Coroutine::run(function () {
            while (true) {
                $protocolState = yield $this->messageProtocol->wait();

                switch ($protocolState) {
                    case MessageProtocol::READY:
                        if ($this->effectiveMaxPacketSize > 0) {
                            return self::WRITABLE;
                        }
                        yield $this->whenWindowAdjusted();
                        break;

                    case MessageProtocol::ENDED:
                        return self::ENDED;

                    default:
                        throw new \Error();
                }
            }
        });

        $waitPromise->then(function ($state) use ($waitPromise) {
            if ($state === self::WRITABLE && $this->waitPromise === $waitPromise) {
                $this->waitPromise = null;
            }
        });

        return $waitPromise;
    }

    public function end()
    {
        if ($this->ended) {
            return;
        }

        $this->ended = true;
        $this->messageProtocol->send(new ChannelEofMessage($this->remoteChannelId));
        $this->endPromise->resolve();
    }

    public function whenEnded(): Promise
    {
        return $this->endPromise;
    }

    private function whenWindowAdjusted(): Promise
    {
        if (!isset($this->windowAdjustPromise) || $this->windowAdjustPromise->isResolved()) {
            $this->windowAdjustPromise = new Promise();
        }
        return $this->windowAdjustPromise;
    }
}
