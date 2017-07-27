<?php
namespace SSH2\Connection;

class ChannelOpenRequest
{
    public static function ofType(string $channelType): ChannelOpenRequest
    {
        return new ChannelOpenRequest($channelType, '', ChannelOptions::create());
    }

    public function getChannelType(): string
    {
        return $this->channelType;
    }

    public function withData(string $data): ChannelOpenRequest
    {
        return new ChannelOpenRequest($this->channelType, $data, $this->channelOptions);
    }

    public function getData(): string
    {
        return $this->data;
    }

    public function withChannelOptions(ChannelOptions $channelOptions): ChannelOpenRequest
    {
        return new ChannelOpenRequest($this->channelType, $this->data, $channelOptions);
    }

    public function getChannelOptions(): ChannelOptions
    {
        return $this->channelOptions;
    }

    public function withInitialWindowSize(int $initialWindowSize): ChannelOpenRequest
    {
        $newConfiguration = $this->channelOptions->withInitialWindowSize($initialWindowSize);
        return $this->withChannelOptions($newConfiguration);
    }

    public function withMaximumPacketSize(int $maximumPacketSize): ChannelOpenRequest
    {
        $newConfiguration = $this->channelOptions->withMaximumPacketSize($maximumPacketSize);
        return $this->withChannelOptions($newConfiguration);
    }

    private $channelType;
    private $data;
    private $channelOptions;

    private function __construct(string $channelType, string $data, ChannelOptions $channelOptions)
    {
        $this->channelType = $channelType;
        $this->data = $data;
        $this->channelOptions = $channelOptions;
    }
}
