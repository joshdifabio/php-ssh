<?php
namespace SSH2\Connection\Internal;

use SSH2\Promise;
use SSH2\ReadableDataBuffer;

class ChannelOutputBuffer implements ReadableDataBuffer
{
    private $outputManager;
    /** @var null|Promise */
    private $waitPromise;
    private $data = '';

    public function __construct(ChannelOutputManager $outputManager)
    {
        $this->outputManager = $outputManager;
        $outputManager->whenEnded()->then(function () {
            if (!isset($this->waitPromise)) {
                $this->waitPromise = new Promise();
            }
            $this->waitPromise->resolve(self::ENDED);
        });
    }

    public function readAll(): string
    {
        if (empty($this->data)) {
            return '';
        }

        $data = $this->data;
        $this->data = '';
        $this->outputManager->handleBufferSpaceFreed(\strlen($data));
        return $data;
    }

    public function read(int $length): string
    {
        if (empty($this->data)) {
            return '';
        }

        $data = \substr($this->data, 0, $length);
        $this->data = \substr($this->data, $length);
        $this->outputManager->handleBufferSpaceFreed(\strlen($data));
        return $data;
    }

    public function wait(): Promise
    {
        if (!isset($this->waitPromise)) {
            $this->waitPromise = new Promise();
        }
        return $this->waitPromise;
    }

    public function whenEnded(): Promise
    {
        return $this->outputManager->whenEnded();
    }

    public function write(string $data)
    {
        $this->data .= $data;

        if (isset($this->waitPromise)) {
            $waitPromise = $this->waitPromise;
            $this->waitPromise = null;
            $waitPromise->resolve(self::READABLE);
        }
    }
}
