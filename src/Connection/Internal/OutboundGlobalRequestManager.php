<?php
namespace SSH2\Connection\Internal;

use SSH2\Connection\GlobalRequestResult;
use SSH2\Connection\Internal\Message\ConnectionMessageNumber;
use SSH2\Connection\Internal\Message\GlobalRequestMessage;
use SSH2\Connection\Internal\Message\RequestFailureMessage;
use SSH2\Connection\Internal\Message\RequestSuccessMessage;
use SSH2\MessageProtocol;

/**
 * @internal
 */
class OutboundGlobalRequestManager
{
    private $underlyingProtocol;
    /** @var callable[] */
    private $resultHandlers = [];
    private $disconnected = false;

    public function __construct(MessageProtocol $underlyingProtocol)
    {
        $this->underlyingProtocol = $underlyingProtocol;
        $this->initObservers();
    }

    public function sendRequest(string $requestName, string $data, callable $resultHandler)
    {
        if ($this->disconnected) {
            $resultHandler(GlobalRequestResult::disconnect());
            return;
        }

        $message = new GlobalRequestMessage($requestName, true, $data);
        $this->resultHandlers[] = $resultHandler;
        $this->underlyingProtocol->send($message);
    }

    private function initObservers()
    {
        $this->underlyingProtocol->onMessageReceived(
            ConnectionMessageNumber::REQUEST_SUCCESS,
            function (RequestSuccessMessage $message) {
                $resultHandler = \array_shift($this->resultHandlers);
                ($resultHandler)(GlobalRequestResult::success($message->getResponseSpecificData()));
            }
        );

        $this->underlyingProtocol->onMessageReceived(
            ConnectionMessageNumber::REQUEST_FAILURE,
            function (RequestFailureMessage $message) {
                $resultHandler = \array_shift($this->resultHandlers);
                ($resultHandler)(GlobalRequestResult::failure());
            }
        );

        $this->underlyingProtocol->whenConnectionClosed()->then(function () {
            $this->disconnected = true;

            $resultHandlers = $this->resultHandlers;
            $this->resultHandlers = [];

            foreach ($resultHandlers as $resultHandler) {
                $resultHandler(GlobalRequestResult::disconnect());
            }
        });
    }
}
