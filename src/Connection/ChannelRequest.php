<?php
namespace SSH2\Connection;

class ChannelRequest
{
    public static function ofType(string $type): ChannelRequest
    {
        return new ChannelRequest($type);
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function withData(string $data): ChannelRequest
    {
        return new ChannelRequest($this->type, $data);
    }

    public function getData(): string
    {
        return $this->data;
    }

    private $type;
    private $data;

    private function __construct(string $type, string $data = '')
    {
        $this->type = $type;
        $this->data = $data;
    }
}
