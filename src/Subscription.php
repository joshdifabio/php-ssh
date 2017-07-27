<?php
namespace SSH2;

use SSH2\Promise;

final class Subscription
{
    private $cancelFn;
    private $cancelled = false;
    private $cancelledPromise;

    public function __construct(callable $cancelFn)
    {
        $this->cancelFn = $cancelFn;
        $this->cancelledPromise = new Promise();
    }

    public function cancel()
    {
        if ($this->cancelled) {
            return;
        }

        $this->cancelled = true;
        ($this->cancelFn)();
        $this->cancelFn = null;
    }

    public function whenCancelled(callable $observer)
    {
        $this->cancelledPromise->then($observer);
    }
}
