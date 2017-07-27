<?php
namespace SSH2\Internal;

use SSH2\Subscription;

/**
 * @internal
 */
class Observers
{
    private $errorHandler;
    private $nextObserverId = 0;
    /** @var Subscription[] */
    private $subscriptions = [];
    private $observers = [];
    private $debug = false;

    public function __construct()
    {
        $this->errorHandler = function () {};
    }

    public function debug()
    {
        $this->debug = true;
    }

    public function fire(...$args)
    {
        foreach ($this->observers as $observer) {
            try {
                $observer(...$args);
            } catch (\Throwable $e) {
                ($this->errorHandler)($e);
            }
        }
    }

    public function add(callable $observer): Subscription
    {
        $observerId = $this->nextObserverId++;
        $this->observers[$observerId] = $observer;
        $subscription = new Subscription(function () use (&$observer, $observerId) {
            unset($this->subscriptions[$observerId]);
            unset($this->observers[$observerId]);
        });
        return $this->subscriptions[$observerId] = $subscription;
    }

    public function cancelAll()
    {
        foreach ($this->subscriptions as $observer) {
            $observer->cancel();
        }
    }

    public function count(): int
    {
        return \count($this->observers);
    }

    public function setErrorHandler(callable $errorHandler)
    {
        $this->errorHandler = $errorHandler;
    }
}
