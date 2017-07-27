<?php
namespace SSH2;

class Promise
{
    private $errorHandler;
    private $result;
    private $observers = [];

    public function __construct()
    {
        $this->errorHandler = function () {};
    }

    public function isResolved(): bool
    {
        return isset($this->result);
    }

    public function resolve(...$args)
    {
        if (isset($this->result)) {
            if ($this->result === $args) {
                return;
            }
            throw new \Error();
        }

        $this->result = $args;

        foreach ($this->observers as $observer) {
            try {
                $observer(...$args);
            } catch (\Throwable $e) {
                ($this->errorHandler)($e);
            }
        }

        unset($this->observers);
    }

    public function then(callable $observer)
    {
        if (isset($this->result)) {
            try {
                $observer(...$this->result);
            } catch (\Throwable $e) {
                ($this->errorHandler)($e);
            }
        } else {
            $this->observers[] = $observer;
        }
    }

    public function setErrorHandler(callable $errorHandler)
    {
        $this->errorHandler = $errorHandler;
    }
}
