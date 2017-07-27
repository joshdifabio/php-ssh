<?php
namespace SSH2\Transport;

use SSH2\Transport\Message\Message;

class MessageBuffer implements ReadableMessageStream, WritableMessageStream
{
    private $messages = [];
    private $listeners = [];
    private $isFiringListeners = false;

    public function read(string $expectedClass = Message::class)
    {
        if (empty($this->messages)) {
            return null;
        }
        return \array_shift($this->messages);
    }

    public function write(Message $message)
    {
        if (!empty($this->messages)) {
            $this->messages[] = $message;
            return;
        }

        $this->messages[] = $message;

        if ($this->isFiringListeners) {
            return;
        }

        $this->isFiringListeners = true;
        try {
            foreach ($this->listeners as $listener) {
                if (!empty($this->messages)) {
                    $listener();
                }
            }
        } finally {
            $this->isFiringListeners = false;
        }
    }

    public function awaitReadable(callable $listener)
    {
        /*
         * fire the listener before adding it to $this->listeners[] to prevent possible overlapping calls
         * to $listener in the case that write() is called before $listener() returns
         */
        if (!empty($this->messages)) {
            $listener();
        }

        $this->listeners[] = $listener;
    }
}
