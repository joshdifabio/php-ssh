<?php
namespace SSH2;

abstract class Coroutine_
{
    public function __construct()
    {
        $this->generator = $this->run();

        try {
            $this->generator->next();
            if ($this->generator->valid()) {
                $this->generatorIsWaiting = true;
            } else {
                $this->succeed($this->generator->getReturn());
            }
        } catch (\Throwable $e) {
            $this->fail($e);
        }
    }

    final public function whenFinished(callable $listener)
    {
        if (isset($this->result)) {
            $listener(...$this->result);
        } else {
            $this->resultListeners[] = $listener;;
        }
    }

    final public function hasFinished(): bool
    {
        return isset($this->result);
    }

    final public function getResult()
    {
        if (!isset($this->result)) {
            return null;
        }

        if (isset($this->result[0])) {
            throw $this->result[0];
        }

        return $this->result[1];
    }

    // internal stuff

    private $result;
    private $resultListeners = [];
    private $generator;
    private $generatorIsWaiting = false;

    abstract protected function run(): \Generator;

    final protected function notify()
    {
        if ($this->generatorIsWaiting) {
            $this->generatorIsWaiting = false;
            try {
                $this->generator->send(null);
                if ($this->generator->valid()) {
                    $this->generatorIsWaiting = true;
                } else {
                    $this->succeed($this->generator->getReturn());
                }
            } catch (\Throwable $e) {
                $this->fail($e);
            }
        }
    }

    private function succeed($value = null)
    {
        if (isset($this->result)) {
            throw new \LogicException;
        }

        $this->result = [null, $value];

        foreach ($this->resultListeners as $listener) {
            $listener(null, $value);
        }

        $this->resultListeners = [];
    }

    private function fail(\Throwable $reason)
    {
        if (isset($this->result)) {
            throw new \LogicException;
        }

        $this->result = [$reason, null];

        foreach ($this->resultListeners as $listener) {
            $listener($reason, null);
        }

        $this->resultListeners = [];
    }
}
