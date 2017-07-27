<?php
namespace SSH2\Connection;

use SSH2\Connection\Internal\Message\GlobalRequestMessage;
use SSH2\Connection\Internal\Message\RequestFailureMessage;
use SSH2\Connection\Internal\Message\RequestSuccessMessage;
use SSH2\Message;

class GlobalRequestHandle
{
    public function getRequestName(): string
    {
        return $this->request->getRequestName();
    }

    public function getRequest(): GlobalRequestMessage
    {
        return $this->request;
    }

    public function succeed(string $data = '')
    {
        if ($this->request->getWantReply()) {
            $this->reply(new RequestSuccessMessage($data));
        }
    }

    public function fail()
    {
        if ($this->request->getWantReply()) {
            $this->reply(new RequestFailureMessage());
        }
    }

    // internal

    private $sendMessageFn;
    private $request;
    private $pending;

    /**
     * @internal
     */
    public function __construct(callable $sendMessageFn, GlobalRequestMessage $request)
    {
        $this->sendMessageFn = $sendMessageFn;
        $this->request = $request;
        $this->pending = $request->getWantReply();
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
