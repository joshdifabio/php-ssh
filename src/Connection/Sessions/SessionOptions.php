<?php
namespace SSH2\Connection\Sessions;

use SSH2\Connection\ChannelOptions;

class SessionOptions
{
    public static function create(): SessionOptions
    {
        return new SessionOptions([], ChannelOptions::create());
    }

    public function withEnvironmentVariables(array $environmentVariables): SessionOptions
    {
        foreach ($environmentVariables as $name => $value) {
            if (!\is_string($name) || \strlen($name) == 0 || !\is_string($value)) {
                throw new \Error();
            }
        }

        return new SessionOptions($environmentVariables, $this->channelOptions);
    }

    public function getEnvironmentVariables(): array
    {
        return $this->environmentVariables;
    }

    public function withChannelOptions(ChannelOptions $channelOptions): SessionOptions
    {
        return new SessionOptions($this->environmentVariables, $channelOptions);
    }

    public function getChannelOptions(): ChannelOptions
    {
        return $this->channelOptions;
    }

    private $environmentVariables = [];
    private $channelOptions;

    public function __construct(array $environmentVariables, ChannelOptions $channelOptions)
    {
        $this->environmentVariables = $environmentVariables;
        $this->channelOptions = $channelOptions;
    }
}
