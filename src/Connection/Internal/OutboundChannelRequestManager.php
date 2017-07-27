<?php
namespace SSH2\Connection\Internal;

use SSH2\Connection\ChannelRequest;
use SSH2\Connection\ChannelRequestResult;
use SSH2\Connection\Internal\Message\ConnectionMessageNumber;
use SSH2\Connection\Internal\Message\ChannelRequestMessage;

/**
 * @internal
 */
class OutboundChannelRequestManager
{
    private $messageProtocol;
    private $remoteChannelId;
    /** @var callable[] */
    private $resultHandlers = [];
    private $writeEnded = false;

    public function __construct(ChannelMessageProtocol $messageProtocol, int $remoteChannelId)
    {
        $this->messageProtocol = $messageProtocol;
        $this->remoteChannelId = $remoteChannelId;
        $this->init();
    }

    public function sendRequest(ChannelRequest $request, callable $resultHandler)
    {
        if ($this->writeEnded) {
            $resultHandler(ChannelRequestResult::CHANNEL_CLOSED);
            return;
        }

        $message = new ChannelRequestMessage($this->remoteChannelId, $request->getType(), true, $request->getData());
        $this->resultHandlers[] = $resultHandler;
        ($this->messageProtocol)($message);
    }

    private function init()
    {
        $this->messageProtocol->onChannelMessageReceived(
            ConnectionMessageNumber::REQUEST_SUCCESS,
            function () {
                $resultHandler = \array_shift($this->resultHandlers);
                ($resultHandler)(ChannelRequestResult::SUCCESS);
            }
        );

        $this->messageProtocol->onChannelMessageReceived(
            ConnectionMessageNumber::REQUEST_FAILURE,
            function () {
                $resultHandler = \array_shift($this->resultHandlers);
                ($resultHandler)(ChannelRequestResult::FAILURE);
            }
        );

        $this->messageProtocol->whenSendEnded()->then(function () {
            $this->writeEnded = true;

            $resultHandlers = $this->resultHandlers;
            $this->resultHandlers = [];
            foreach ($resultHandlers as $resultHandler) {
                $resultHandler(ChannelRequestResult::CHANNEL_CLOSED);
            }
        });
    }
}
