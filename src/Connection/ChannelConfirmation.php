<?php
namespace SSH2\Connection;

class ChannelConfirmation
{
    public static function create(): ChannelConfirmation
    {
        return new ChannelConfirmation('', ChannelOptions::create());
    }

    public function withData(string $data): ChannelConfirmation
    {
        return new ChannelConfirmation($data, $this->channelOptions);
    }

    public function getData(): string
    {
        return $this->data;
    }

    public function withChannelOptions(ChannelOptions $channelOptions): ChannelConfirmation
    {
        return new ChannelConfirmation($this->data, $channelOptions);
    }

    public function getChannelOptions(): ChannelOptions
    {
        return $this->channelOptions;
    }

    public function withInitialWindowSize(int $initialWindowSize): ChannelConfirmation
    {
        $newConfiguration = $this->channelOptions->withInitialWindowSize($initialWindowSize);
        return $this->withChannelOptions($newConfiguration);
    }

    public function withMaximumPacketSize(int $maximumPacketSize): ChannelConfirmation
    {
        $newConfiguration = $this->channelOptions->withMaximumPacketSize($maximumPacketSize);
        return $this->withChannelOptions($newConfiguration);
    }

    private $data;
    private $channelOptions;

    private function __construct(string $data, ChannelOptions $channelOptions)
    {
        $this->data = $data;
        $this->channelOptions = $channelOptions;
    }
}
