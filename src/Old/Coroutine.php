<?php
namespace SSH2;

/**
 * Creates a promise from a generator function yielding promises.
 *
 * When a promise is yielded, execution of the generator is interrupted until the promise is resolved. A success
 * value is sent into the generator, while a failure reason is thrown into the generator. Using a coroutine,
 * asynchronous code can be written without callbacks and be structured like synchronous code.
 */
final class Coroutine extends Future {
    /** @var \Generator */
    private $generator;

    /** @var callable(\Throwable|null $exception, mixed $value): void */
    private $when;

    /**
     * @param \Generator $generator
     */
    public function __construct(\Generator $generator) {
        $this->generator = $generator;

        /**
         * @param \Throwable|null $exception Exception to be thrown into the generator.
         * @param mixed           $value Value to be sent into the generator.
         */
        $this->when = function ($exception, $value) {
            try {
                if ($exception) {
                    // Throw exception at current execution point.
                    $yielded = $this->generator->throw($exception);
                } else {
                    // Send the new value and execute to next yield statement.
                    $yielded = $this->generator->send($value);
                }

                if (!$yielded instanceof Future) {
                    if (!$this->generator->valid()) {
                        $this->succeed($this->generator->getReturn());
                        return;
                    }

                    throw new \Error('Coroutines must only yield instances of ' . Future::class);
                }

                $yielded->when($this->when);
            } catch (\Throwable $exception) {
                $this->dispose($exception);
            }
        };

        try {
            $yielded = $this->generator->current();

            if (!$yielded instanceof Future) {
                if (!$this->generator->valid()) {
                    $this->succeed($this->generator->getReturn());
                    return;
                }

                throw new \Error('Coroutines must only yield instances of ' . Future::class);
            }

            $yielded->when($this->when);
        } catch (\Throwable $exception) {
            $this->dispose($exception);
        }
    }

    /**
     * Runs the generator to completion then fails the coroutine with the given exception.
     *
     * @param \Throwable $exception
     */
    private function dispose(\Throwable $exception) {
        if ($this->generator->valid()) {
            try {
                try {
                    // Ensure generator has run to completion to avoid throws from finally blocks on destruction.
                    do {
                        $this->generator->throw($exception);
                    } while ($this->generator->valid());
                } finally {
                    // Throw from finally to attach any exception thrown from generator as previous exception.
                    throw $exception;
                }
            } catch (\Throwable $exception) {
                // $exception will be used to fail the coroutine.
            }
        }

        $this->when = $this->generator = null;
        $this->fail($exception);
    }
}
