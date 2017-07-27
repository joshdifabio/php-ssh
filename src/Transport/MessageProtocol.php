<?php
namespace SSH2\Transport;

use SSH2\Coroutine;
use SSH2\Future;
use SSH2\Transport\Message\Message;

abstract class MessageProtocol
{
    protected $receivedMessageBuffer;
    protected $sentMessageBuffer;

    private $future;
    private $finished = false;

    public function __construct()
    {
        $this->receivedMessageBuffer = new MessageBuffer;
        $this->sentMessageBuffer = new MessageBuffer;
        $generatorOrFuture = $this->runProtocol();
        if ($generatorOrFuture instanceof \Generator) {
            $generatorOrFuture = new Coroutine($generatorOrFuture);
        } elseif (!$generatorOrFuture instanceof Future) {
            throw new \Error();
        }
        $this->future = $generatorOrFuture;
        $this->future->onResolve(function () { $this->finished = true; })
    }

    final public function run(): Future
    {
        return $this->future;
    }

    final public function hasFinished(): bool
    {
        return $this->finished;
    }

    final public function receiveMessage(Message $message)
    {
        $this->receivedMessageBuffer->write($message);
    }

    final public function getSentMessageBuffer(): ReadableMessageStream
    {
        return $this->sentMessageBuffer;
    }

    abstract protected function runProtocol();
}
