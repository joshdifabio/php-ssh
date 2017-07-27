<?php
namespace SSH2\Connection;

use SSH2\Connection\Internal\Message\ChannelFailureMessage;
use SSH2\Connection\Internal\Message\ChannelRequestMessage;
use SSH2\Connection\Internal\Message\ChannelSuccessMessage;
use SSH2\Message;

class ChannelRequestHandle
{
    public function getRequestType(): string
    {
        return $this->request->getRequestType();
    }

    public function getRequest(): ChannelRequestMessage
    {
        return $this->request;
    }

    public function succeed()
    {
        if ($this->request->getWantReply()) {
            $this->reply(new ChannelSuccessMessage($this->remoteChannelId));
        }
    }

    public function fail()
    {
        if ($this->request->getWantReply()) {
            $this->reply(new ChannelFailureMessage($this->remoteChannelId));
        }
    }

    // internal

    private $sendMessageFn;
    private $request;
    private $remoteChannelId;
    private $pending;

    /**
     * @internal
     */
    public function __construct(callable $sendMessageFn, ChannelRequestMessage $message, int $remoteChannelId)
    {
        $this->sendMessageFn = $sendMessageFn;
        $this->request = $message;
        $this->remoteChannelId = $remoteChannelId;
        $this->pending = $message->getWantReply();
    }

    /**
     * @internal
     */
    public function __destruct()
    {
        if ($this->pending) {
            $this->fail();
        }
    }

    private function reply(Message $message)
    {
        if (!$this->pending) {
            throw new \Exception();
        }

        $this->pending = false;
        ($this->sendMessageFn)($message);
    }
}
