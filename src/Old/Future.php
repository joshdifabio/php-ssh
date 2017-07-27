<?php
namespace SSH2;

class Future
{
    private $result;
    private $listeners = [];

    public function onResolve(callable $listener)
    {
        if (isset($this->result)) {
            $listener(...$this->result);
        } else {
            $this->listeners[] = $listener;
        }
    }

    public function succeed($value = null)
    {
        if (isset($this->result)) {
            throw new \Exception('Future already resolved.');
        }

        $this->result = [null, $value];
        $listeners = $this->listeners;
        $this->listeners = [];

        foreach ($listeners as $listener) {
            $listener(...$this->result);
        }
    }

    public function fail(\Throwable $reason)
    {
        if (isset($this->result)) {
            throw new \Exception('Future already resolved.');
        }

        $this->result = [$reason, null];
        $listeners = $this->listeners;
        $this->listeners = [];

        foreach ($listeners as $listener) {
            $listener(...$this->result);
        }
    }
}
