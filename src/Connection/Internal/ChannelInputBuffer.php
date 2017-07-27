<?php
namespace SSH2\Connection\Internal;

use SSH2\Promise;
use SSH2\WritableDataBuffer;

class ChannelInputBuffer implements WritableDataBuffer
{
    private $inputManager;
    private $dataTypeCode;
    private $buffer = '';
    private $finishPromise;
    /** @var null|Promise */
    private $waitPromise;
    private $ended = false;
    private $finished = false;
    private $draining = false;

    public function __construct(ChannelInputManager $inputManager, int $dataTypeCode = null)
    {
        $this->inputManager = $inputManager;
        $this->dataTypeCode = $dataTypeCode;
        $this->finishPromise = new Promise();

        $inputManager->whenEnded()->then(function () {
            $this->finish();
        });
    }

    public function write(string $data): int
    {
        if ($this->ended) {
            return $this->finished ? self::FINISHED : self::ENDED;
        }

        if (!empty($this->buffer) || $this->inputManager->effectiveMaxPacketSize <= 0) {
            $this->buffer .= $data;
            $this->inputManager->wait()->then([$this, 'drainIfPossible']);
            return self::BLOCKED;
        }

        if ($this->inputManager->effectiveMaxPacketSize >= \strlen($data)) {
            $this->inputManager->sendData($data, $this->dataTypeCode);
            return self::WRITABLE;
        }

        $dataToSend = \substr($data, 0, $this->inputManager->effectiveMaxPacketSize);
        $this->buffer = \substr($data, $this->inputManager->effectiveMaxPacketSize);
        $busState = $this->inputManager->sendData($dataToSend, $this->dataTypeCode);
        if ($busState === ChannelInputManager::WRITABLE) {
            $this->drainIfPossible(ChannelInputManager::WRITABLE);
        } else {
            $this->inputManager->wait()->then([$this, 'drainIfPossible']);
        }

        return empty($this->buffer) ? self::WRITABLE : self::BLOCKED;
    }

    public function end()
    {
        if ($this->ended) {
            return;
        }

        $this->ended = true;

        if (empty($this->buffer)) {
            $this->finish();
        } elseif (isset($this->waitPromise)) {
            $waitPromise = $this->waitPromise;
            $this->waitPromise = null;
            $waitPromise->resolve(self::ENDED);
        }
    }

    public function wait(): Promise
    {
        if (isset($this->waitPromise)) {
            return $this->waitPromise;
        }

        $waitPromise = new Promise();

        if ($this->ended) {
            $waitPromise->resolve(self::ENDED);
            return $waitPromise;
        }

        if (!empty($this->buffer)) {
            $this->waitPromise = $waitPromise;
        } else {
            $this->inputManager->wait()->then(function ($busState) use ($waitPromise) {
                if (!$waitPromise->isResolved() && $busState === ChannelInputManager::WRITABLE) {
                    $waitPromise->resolve(self::WRITABLE);
                }
            });
        }

        return $waitPromise;
    }

    public function whenFinished(): Promise
    {
        return $this->finishPromise;
    }

    public function drainIfPossible($busState)
    {
        if ($busState !== ChannelInputManager::WRITABLE || $this->draining) {
            return;
        }

        $this->draining = true;
        try {
            while (!empty($this->buffer) && $this->inputManager->effectiveMaxPacketSize > 0) {
                if ($this->inputManager->effectiveMaxPacketSize >= \strlen($this->buffer)) {
                    $data = $this->buffer;
                    $this->buffer = '';
                    $busState = $this->inputManager->sendData($data, $this->dataTypeCode);
                    if ($busState !== self::WRITABLE) {
                        break;
                    }
                } else {
                    $dataToSend = \substr($this->buffer, 0, $this->inputManager->effectiveMaxPacketSize);
                    $this->buffer = \substr($this->buffer, $this->inputManager->effectiveMaxPacketSize);
                    $this->inputManager->sendData($dataToSend, $this->dataTypeCode);
                    break;
                }
            }
        } finally {
            $this->draining = false;
        }

        if (!empty($this->buffer)) {
            $this->inputManager->wait()->then([$this, 'drainIfPossible']);
        } elseif ($this->ended) {
            $this->finish();
        } elseif (isset($this->waitPromise)) {
            if ($busState === self::WRITABLE) {
                $this->waitPromise->resolve();
            } else {
                $waitPromise = $this->waitPromise;
                $this->inputManager->wait()->then(function ($busState) use ($waitPromise) {
                    if (!$waitPromise->isResolved() && $busState === ChannelInputManager::WRITABLE) {
                        $waitPromise->resolve(self::WRITABLE);
                    }
                });
            }
        }
    }

    private function finish()
    {
        if ($this->finished) {
            return;
        }

        $this->ended = true;
        $this->finished = true;

        if (!isset($this->waitPromise)) {
            $this->waitPromise = new Promise();
        }
        $this->waitPromise->resolve(self::FINISHED);
        $this->finishPromise->resolve();
    }
}
