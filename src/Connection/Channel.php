<?php
namespace SSH2\Connection;

use SSH2\Connection\Internal\ChannelInputManager;
use SSH2\Connection\Internal\ChannelMessageProtocol;
use SSH2\Connection\Internal\ChannelOutputManager;
use SSH2\Connection\Internal\InboundChannelRequestManager;
use SSH2\Connection\Internal\OutboundChannelRequestManager;
use SSH2\Connection\Internal\Message\ChannelCloseMessage;
use SSH2\Connection\Internal\Message\ChannelConfigurationMessage;
use SSH2\Connection\Internal\Message\ChannelRequestMessage;
use SSH2\Connection\Internal\Message\ConnectionMessageNumber;
use SSH2\Internal\Observers;
use SSH2\Promise;
use SSH2\Subscription;

class Channel
{
    const OPEN = 1;
    const CLOSING = 2;
    const CLOSED = 3;

    public function getState(): int
    {
        return $this->state;
    }

    public function getOutput(): ChannelOutput
    {
        return $this->output;
    }

    public function getInput(): ChannelInput
    {
        return $this->input;
    }

    /**
     * @see ChannelRequestResult
     */
    public function sendRequest(ChannelRequest $request): Promise
    {
        $promise = new Promise();
        $this->outboundRequestManager->sendRequest($request, [$promise, 'resolve']);
        return $promise;
    }

    public function sendRequestAndIgnoreReply(ChannelRequest $request)
    {
        $this->messageProtocol->send(new ChannelRequestMessage($this->remoteChannelId, $request->getType(), false, $request->getData()));
    }

    /**
     * @see ChannelRequestHandle
     */
    public function onRequestReceived(callable $observer): Subscription
    {
        return $this->inboundRequestManager->onRequest($observer);
    }

    public function onStateChange(callable $observer): Subscription
    {
        return $this->stateChangeObservers->add($observer);
    }

    public function close()
    {
        switch ($this->state) {
            case self::CLOSING;
            case self::CLOSED;
                // nothing to do
                break;

            default:
                $this->state = self::CLOSING;
                $this->stateChangeObservers->fire($this->state);
                $this->sendCloseMessage();
                break;
        }
    }

    public function whenClosed(): Promise
    {
        return $this->closePromise;
    }

    // internal

    private $messageProtocol;
    private $remoteChannelId;
    private $state = self::OPEN;
    private $output;
    private $input;
    private $inboundRequestManager;
    private $outboundRequestManager;
    private $stateChangeObservers;
    private $closePromise;

    /**
     * @internal
     */
    public function __construct(
        ChannelMessageProtocol $messageProtocol,
        ChannelConfigurationMessage $sentMessage,
        ChannelConfigurationMessage $receivedMessage
    ) {
        $this->messageProtocol = $messageProtocol;
        $this->remoteChannelId = $receivedMessage->getSenderChannel();
        $this->stateChangeObservers = new Observers();
        $this->closePromise = new Promise;

        $this->output = new ChannelOutputManager(
            $messageProtocol,
            $this->remoteChannelId,
            $sentMessage->getSenderConfiguration()->getInitialWindowSize())
        ;

        $remoteConfiguration = $receivedMessage->getSenderConfiguration();
        $inputBus = new ChannelInputManager(
            $messageProtocol,
            $this->remoteChannelId,
            $remoteConfiguration->getInitialWindowSize(),
            $remoteConfiguration->getMaximumPacketSize()
        );
        $this->input = new ChannelInput($inputBus);

        $this->inboundRequestManager = new InboundChannelRequestManager($messageProtocol, $this->remoteChannelId);
        $this->outboundRequestManager = new OutboundChannelRequestManager($messageProtocol, $this->remoteChannelId);

        $this->stateChangeObservers->add(function ($newState) {
            if ($newState === self::CLOSED) {
                $this->closePromise->resolve();
            }
        });

        $messageProtocol->onChannelMessageReceived(ConnectionMessageNumber::CHANNEL_CLOSE, function () {
            switch ($this->state) {
                case self::OPEN:
                    $this->state = self::CLOSED;
                    $this->stateChangeObservers->fire($this->state);
                    $this->sendCloseMessage();
                    break;

                case self::CLOSING:
                    $this->state = self::CLOSED;
                    $this->stateChangeObservers->fire($this->state);
                    break;
            }
        });

        $messageProtocol->whenConnectionClosed()->then(function () {
            if ($this->state !== self::CLOSED) {
                $this->state = self::CLOSED;
                $this->stateChangeObservers->fire($this->state);
            }
        });
    }

    private function sendCloseMessage()
    {
        $this->messageProtocol->send(new ChannelCloseMessage($this->remoteChannelId));
    }
}
