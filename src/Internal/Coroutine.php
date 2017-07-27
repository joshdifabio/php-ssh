<?php
namespace SSH2\Internal;

use SSH2\Promise;

class Coroutine
{
    public static function run(callable $generatorFn, ...$args): Promise
    {
        $coroutine = new Coroutine($generatorFn(...$args));
        return $coroutine->promise;
    }

    public static function wrap(callable $generatorFn): callable
    {
        return function (...$args) use ($generatorFn) {
            $coroutine = new Coroutine($generatorFn(...$args));
            return $coroutine->promise;
        };
    }

    public static function create(\Generator $generator): Promise
    {
        $coroutine = new Coroutine($generator);
        return $coroutine->promise;
    }

    private $promise;
    private $generator;
    private $immediate = true;
    private $value;

    private function __construct(\Generator $generator)
    {
        $this->generator = $generator;
        $this->promise = new Promise;

        $this->promise->then(function () {
            $this->onResolve = function () {};
        });

        $this->onResolve = function ($value = null) {
            $this->value = $value;

            if (!$this->immediate) {
                $this->immediate = true;
                return;
            }

            do {
                // Send the new value and execute to next yield statement.
                $yielded = $this->generator->send($this->value);
                if (!$yielded instanceof Promise) {
                    if ($this->generator->valid()) {
                        throw new \Error();
                    }
                    $this->promise->resolve($this->generator->getReturn());
                    return;
                }
                $this->immediate = false;
                $yielded->then($this->onResolve);
            } while ($this->immediate);

            $this->immediate = true;
            $this->value = null;
        };

        $yielded = $this->generator->current();

        if (!$yielded instanceof Promise) {
            if ($this->generator->valid()) {
                throw new \Error();
            }
            $this->promise->resolve($this->generator->getReturn());
            return;
        }

        $yielded->then($this->onResolve);
    }
}
