<?php
namespace SSH2\Connection\PortForwarding;

class TunnelOpenResult
{
    const SUCCESS = 'success';
    const FAILURE = 'failure';

    public function getType(): string
    {

    }

    public function getTunnel(): ?Tunnel
    {

    }

    public static function success(Tunnel $tunnel): TunnelOpenResult
    {

    }

    public static function failure(): TunnelOpenResult
    {

    }
}
