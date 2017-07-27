<?php
namespace SSH2\Transport\Message;

use SSH2\Transport\Message\Message;
use SSH2\Transport\TransportMessageNumber;

class ServiceRequestMessage extends Message
{
    private $serviceName;

    public function __construct(string $serviceName)
    {
        $this->serviceName = $serviceName;

        $binary = \pack('CNa*', TransportMessageNumber::SERVICE_REQUEST, \strlen($serviceName), $serviceName);
        parent::__construct(TransportMessageNumber::SERVICE_REQUEST, $binary);
    }

    public function getServiceName(): string
    {
        return $this->serviceName;
    }
}
