<?php
namespace SSH2\Connection\Sessions;

use SSH2\Connection\ChannelOpenRequest;
use SSH2\Connection\ChannelRequest;

class SessionOpenRequest
{
    public static function startShell(): SessionOpenRequest
    {
        $mainRequest = ChannelRequest::ofType('shell');
        return new SessionOpenRequest($mainRequest);
    }

    public static function exec(string $command): SessionOpenRequest
    {
        $mainRequest = ChannelRequest::ofType('exec')->withData($command);
        return new SessionOpenRequest($mainRequest);
    }

    public static function startSubsystem(string $subsystemName): SessionOpenRequest
    {
        $mainRequest = ChannelRequest::ofType('subsystem')->withData($subsystemName);
        return new SessionOpenRequest($mainRequest);
    }

    public function withOptions(SessionOptions $options): SessionOpenRequest
    {
        return new SessionOpenRequest($this->mainChannelRequest, $options);
    }

    public function getOptions(): SessionOptions
    {
        return $this->options;
    }

    // internal

    private $mainChannelRequest;
    private $options;

    private function __construct(ChannelRequest $mainRequest, SessionOptions $options = null)
    {
        $this->mainChannelRequest = $mainRequest;
        $this->options = $options ?? SessionOptions::create();
    }

    /**
     * @internal
     */
    public function getChannelOpenRequest(): ChannelOpenRequest
    {
        return ChannelOpenRequest::ofType('session')->withChannelOptions($this->options->getChannelOptions());
    }

    /**
     * @internal
     *
     * @return ChannelRequest[]
     */
    public function getEnvChannelRequests(): array
    {
        $requests = [];
        foreach ($this->options->getEnvironmentVariables() as $name => $value) {
            $requests[] = ChannelRequest::ofType('env')->withData(\pack('Na*Na*', \strlen($name), $name, \strlen($value), $value));
        }
        return $requests;
    }

    /**
     * @internal
     */
    public function getMainChannelRequest(): ChannelRequest
    {
        return $this->mainChannelRequest;
    }
}
