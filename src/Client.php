<?php
namespace SSH2;

use SSH2\Connection\ConnectionProtocol;
use SSH2\Connection\PortForwarding\Tunnel;
use SSH2\Connection\PortForwarding\TunnelConfiguration;
use SSH2\Connection\PortForwarding\TunnelOpenResult;
use SSH2\Connection\Sessions\Session;
use SSH2\Connection\Sessions\SessionOpenRequest;
use SSH2\Connection\Sessions\SessionOpenResult;
use SSH2\Connection\Sessions\SessionOptions;

class Client
{
    private $connectionProtocol;

    public function __construct(ConnectionProtocol $connectionProtocol)
    {
        $this->connectionProtocol = $connectionProtocol;
    }

    /**
     * @see SessionOpenResult
     */
    public function startShell(SessionOptions $options = null): Promise
    {
        $sessionOpenRequest = SessionOpenRequest::startShell()
            ->withOptions($options ?? SessionOptions::create());
        return Session::open($this->connectionProtocol, $sessionOpenRequest);
    }

    /**
     * @see SessionOpenResult
     */
    public function exec(string $command, SessionOptions $options = null): Promise
    {
        $sessionOpenRequest = SessionOpenRequest::exec($command)
            ->withOptions($options ?? SessionOptions::create());
        return Session::open($this->connectionProtocol, $sessionOpenRequest);
    }

    /**
     * @see SessionOpenResult
     */
    public function startSubsystem(string $subsystemName, SessionOptions $options = null): Promise
    {
        $sessionOpenRequest = SessionOpenRequest::startSubsystem($subsystemName)
            ->withOptions($options ?? SessionOptions::create());
        return Session::open($this->connectionProtocol, $sessionOpenRequest);
    }

    /**
     * @see TunnelOpenResult
     */
    public function openTunnel(TunnelConfiguration $configuration): Promise
    {
        return Tunnel::open($this->connectionProtocol, $configuration);
    }
}
