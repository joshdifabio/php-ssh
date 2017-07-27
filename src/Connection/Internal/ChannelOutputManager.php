<?php
namespace SSH2\Connection\Internal;

use SSH2\Connection\ChannelOutput;
use SSH2\Connection\Internal\Message\ChannelDataMessage;
use SSH2\Connection\Internal\Message\ChannelExtendedDataMessage;
use SSH2\Connection\Internal\Message\ChannelWindowAdjustMessage;
use SSH2\Connection\Internal\Message\ConnectionMessageNumber;
use SSH2\Promise;
use SSH2\ReadableDataBuffer;

class ChannelOutputManager implements ChannelOutput
{
    private $messageProtocol;
    private $remoteChannelId;
    private $standardDataBuffer;
    private $extendedDataBuffers = [];
    private $windowSpaceRemaining;
    private $bufferSpaceAvailable;
    private $bufferSize;
    private $ended = false;
    private $standardDataIgnored = false;
    private $ignoredExtendedDataTypes = [];
    private $endPromise;

    public function __construct(ChannelMessageProtocol $messageProtocol, int $remoteChannelId, int $initialWindowSize)
    {
        $this->messageProtocol = $messageProtocol;
        $this->remoteChannelId = $remoteChannelId;
        $this->bufferSpaceAvailable = $this->bufferSize = $this->windowSpaceRemaining = $initialWindowSize;
        $this->endPromise = new Promise();
        $this->standardDataBuffer = new ChannelOutputBuffer($this);

        $messageProtocol->whenReceiveEnded()->then(function () {
            $this->end();
        });
        $messageProtocol->onChannelMessageReceived(ConnectionMessageNumber::CHANNEL_EOF, function () {
            $this->end();
        });

        $messageProtocol->onChannelMessageReceived(ConnectionMessageNumber::CHANNEL_DATA, function (ChannelDataMessage $message) {
            if ($this->standardDataIgnored) {
                $this->processIgnoredData(\strlen($message->getData()));
            } else {
                $data = $this->processReceivedData($message->getData());
                $this->standardDataBuffer->write($data);
            }
        });
        $messageProtocol->onChannelMessageReceived(ConnectionMessageNumber::CHANNEL_EXTENDED_DATA, function (ChannelExtendedDataMessage $message) {
            if ($this->ignoredExtendedDataTypes[$message->getDataTypeCode()] ?? false) {
                $this->processIgnoredData(\strlen($message->getData()));
            } else {
                $data = $this->processReceivedData($message->getData());
                $this->getExtendedData($message->getDataTypeCode())->write($data);
            }
        });
    }

    public function getStandardData(): ReadableDataBuffer
    {
        return $this->standardDataBuffer;
    }

    public function ignoreStandardData()
    {
        $this->standardDataIgnored = true;
    }

    public function getExtendedData(int $dataType): ReadableDataBuffer
    {
        if (!isset($this->extendedDataBuffers[$dataType])) {
            $this->extendedDataBuffers[$dataType] = new ChannelOutputBuffer($this);
        }
        return $this->extendedDataBuffers[$dataType];
    }

    public function ignoreExtendedData(array $exceptedDataTypes = [])
    {

    }

    public function setBufferSize(int $bufferSize)
    {
        $adjustment = $bufferSize - $this->bufferSize;
        $this->bufferSize = $bufferSize;
        $this->handleBufferSpaceFreed($adjustment);
    }

    public function handleBufferSpaceFreed(int $size)
    {
        $this->bufferSpaceAvailable += $size;
        if ($this->ended) {
            return;
        }
        $bytesToAddToWindow = $this->bufferSpaceAvailable - $this->windowSpaceRemaining;
        if ($bytesToAddToWindow > 0) {
            $this->windowSpaceRemaining += $bytesToAddToWindow;
            $this->messageProtocol->send(new ChannelWindowAdjustMessage($this->remoteChannelId, $bytesToAddToWindow));
        }
    }

    public function whenEnded(): Promise
    {
        return $this->endPromise;
    }

    private function processReceivedData(string $data): string
    {
        $dataSize = \strlen($data);
        if ($dataSize + 4 > $this->windowSpaceRemaining) {
            if ($this->windowSpaceRemaining < 4) {
                $data = '';
            } else {
                $data = \substr($data, 0, $this->windowSpaceRemaining - 4);
            }
            $this->windowSpaceRemaining = 0;
            $this->bufferSpaceAvailable -= $this->windowSpaceRemaining;
        } else {
            $this->windowSpaceRemaining -= ($dataSize + 4);
            $this->bufferSpaceAvailable -= $dataSize;
        }
        return $data;
    }

    private function processIgnoredData(int $dataSize)
    {
        $dataSize += 4;
        if ($dataSize > $this->windowSpaceRemaining) {
            $this->windowSpaceRemaining = 0;
        } else {
            $this->windowSpaceRemaining -= $dataSize;
        }
        $this->handleBufferSpaceFreed(0);
    }

    private function end()
    {
        if (!$this->ended) {
            $this->ended = true;
            $this->endPromise->resolve();
        }
    }
}
