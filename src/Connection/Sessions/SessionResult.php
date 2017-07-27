<?php
namespace SSH2\Connection\Sessions;

class SessionResult
{
    public function getExitStatus(): ?int
    {
        return $this->exitStatus;
    }

    public function getExitReason(): ?ExitReason
    {
        return $this->exitReason;
    }

    // internal

    private $exitStatus;
    private $exitReason;

    /**
     * @internal
     */
    public function __construct(int $exitStatus = null, ExitReason $exitReason = null)
    {
        $this->exitStatus = $exitStatus;
        $this->exitReason = $exitReason;
    }
}
