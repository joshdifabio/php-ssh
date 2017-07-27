<?php
namespace SSH2\Connection\Internal;

use SSH2\Connection\GlobalRequestHandle;
use SSH2\Connection\Internal\Message\ConnectionMessageNumber;
use SSH2\Connection\Internal\Message\GlobalRequestMessage;
use SSH2\Internal\Observers;
use SSH2\Message;
use SSH2\MessageProtocol;
use SSH2\Subscription;

/**
 * @internal
 */
class InboundGlobalRequestManager
{
    private $underlyingProtocol;
    private $observers;
    /** @var GlobalRequestHandle[] */
    private $requestQueue = [];
    private $replyPending = false;
    private $firing = false;
    private $disconnected = false;

    public function __construct(MessageProtocol $underlyingProtocol)
    {
        $this->underlyingProtocol = $underlyingProtocol;
        $this->observers = new Observers();
        $this->initObservers();
    }

    public function onRequest(callable $observer): Subscription
    {
        if ($this->disconnected) {
            return new Subscription(function () {});
        }

        return $this->observers->add($observer);
    }

    public function sendReply(Message $message)
    {
        $this->underlyingProtocol->send($message);
        $this->replyPending = false;
        $this->fire();
    }

    private function initObservers()
    {
        $this->underlyingProtocol->onMessageReceived(
            ConnectionMessageNumber::GLOBAL_REQUEST,
            function (GlobalRequestMessage $message) {
                $this->requestQueue[] = new GlobalRequestHandle([$this, 'sendReply'], $message);
                $this->fire();
            }
        );

        $this->underlyingProtocol->whenConnectionClosed()->then(function () {
            $this->disconnected = true;
            $this->observers->cancelAll();
        });
    }

    private function fire()
    {
        if ($this->firing) {
            return;
        }

        $this->firing = true;
        try {
            while (!$this->replyPending && $request = \array_shift($this->requestQueue)) {
                $this->replyPending = $request->getRemoteWantsReply();
                $this->observers->fire($request);
            }
        } finally {
            $this->firing = false;
        }
    }
}
