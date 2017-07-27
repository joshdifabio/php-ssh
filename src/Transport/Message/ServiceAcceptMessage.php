<?php
namespace SSH2\Transport\Message;

use SSH2\Transport\Message\Message;
use SSH2\Transport\TransportMessageNumber;

class ServiceAcceptMessage extends Message
{
    private $serviceName;

    public function __construct(string $serviceName)
    {
        $this->serviceName = $serviceName;

        $binary = \pack('CNa*', TransportMessageNumber::SERVICE_ACCEPT, \strlen($serviceName), $serviceName);
        parent::__construct(TransportMessageNumber::SERVICE_ACCEPT, $binary);
    }

    public function getServiceName(): string
    {
        return $this->serviceName;
    }
}
