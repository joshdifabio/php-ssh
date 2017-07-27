<?php
namespace SSH2\Connection\Internal;

use SSH2\Connection\ChannelRequestHandle;
use SSH2\Connection\Internal\Message\ChannelRequestMessage;
use SSH2\Connection\Internal\Message\ConnectionMessageNumber;
use SSH2\Internal\Observers;
use SSH2\Message;
use SSH2\Subscription;

/**
 * @internal
 */
class InboundChannelRequestManager
{
    private $messageProtocol;
    private $remoteChannelId;
    private $observers;
    /** @var ChannelRequestHandle[] */
    private $requestQueue = [];
    private $replyPending = false;
    private $firing = false;
    private $readEnded = false;

    public function __construct(ChannelMessageProtocol $messageProtocol, int $remoteChannelId)
    {
        $this->messageProtocol = $messageProtocol;
        $this->remoteChannelId = $remoteChannelId;
        $this->observers = new Observers();
        $this->init();
    }

    public function onRequest(callable $observer): Subscription
    {
        if ($this->readEnded) {
            return new Subscription(function () {});
        }

        return $this->observers->add($observer);
    }

    public function sendReply(Message $message)
    {
        $this->messageProtocol->send($message);
        $this->replyPending = false;
        $this->fire();
    }

    private function init()
    {
        $this->messageProtocol->onChannelMessageReceived(
            ConnectionMessageNumber::CHANNEL_REQUEST,
            function (ChannelRequestMessage $message) {
                $this->requestQueue[] = new ChannelRequestHandle([$this, 'sendReply'], $message, $this->remoteChannelId);
                $this->fire();
            }
        );

        $this->messageProtocol->whenReceiveEnded()->then(function () {
            $this->readEnded = true;
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
