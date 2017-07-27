<?php
namespace SSH2\Connection;

class ChannelOptions
{
    public static function create(): ChannelOptions
    {
        return new ChannelOptions();
    }

    public function withInitialWindowSize(int $initialWindowSize): ChannelOptions
    {
        if ($initialWindowSize < 0) {
            throw new \Error();
        }

        $clone = clone $this;
        $clone->initialWindowSize = $initialWindowSize;
        return $clone;
    }

    public function getInitialWindowSize(): int
    {
        return $this->initialWindowSize;
    }

    public function withMaximumPacketSize(int $maximumPacketSize): ChannelOptions
    {
        if ($maximumPacketSize < 32768) {
            throw new \Error();
        }

        $clone = clone $this;
        $clone->maximumPacketSize = $maximumPacketSize;
        return $clone;
    }

    public function getMaximumPacketSize(): int
    {
        return $this->maximumPacketSize;
    }

    // internal

    private $initialWindowSize = 256 * 1024;
    private $maximumPacketSize = 256 * 1024;

    private function __construct()
    {

    }
}
