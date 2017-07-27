<?php
namespace SSH2\Connection\Sessions;

use SSH2\Connection\Channel;
use SSH2\Connection\ChannelOpenResult;
use SSH2\Connection\ChannelRequest;
use SSH2\Connection\ChannelRequestHandle;
use SSH2\Connection\ChannelRequestResult;
use SSH2\Connection\ConnectionProtocol;
use SSH2\Internal\Coroutine;
use SSH2\Promise;
use SSH2\ReadableDataBuffer;
use SSH2\WritableDataBuffer;

class Session
{
    /**
     * @see SessionOpenResult
     */
    public static function open(ConnectionProtocol $connection, SessionOpenRequest $sessionOpenRequest): Promise
    {
        return Coroutine::run(function () use ($connection, $sessionOpenRequest) {
            /** @var ChannelOpenResult $channelOpenResult */
            $channelOpenResult = yield $connection->openChannel($sessionOpenRequest->getChannelOpenRequest());

            switch ($channelOpenResult->getType()) {
                case ChannelOpenResult::SUCCESS:
                    $session = new Session($channelOpenResult->getChannel());
                    $result = yield from $session->init($sessionOpenRequest);
                    return $result;

                case ChannelOpenResult::FAILURE:
                    return SessionOpenResult::failure();

                case ChannelOpenResult::DISCONNECT:
                    return SessionOpenResult::disconnect();

                default:
                    throw new \Error();
            }
        });
    }

    public function getStdin(): WritableDataBuffer
    {
        return $this->channel->getInput()->getStandardData();
    }

    public function getStdout(): ReadableDataBuffer
    {
        return $this->channel->getOutput()->getStandardData();
    }

    public function getStderr(): ReadableDataBuffer
    {
        return $this->channel->getOutput()->getExtendedData(1);
    }

    public function setOutputBufferSize(int $bufferSize)
    {
        $this->channel->getOutput()->setBufferSize($bufferSize);
    }

    public function signal(string $signalName)
    {
        $channelRequest = ChannelRequest::ofType('signal')->withData(\pack('Na*', \strlen($signalName), $signalName));
        $this->channel->sendRequestAndIgnoreReply($channelRequest);
    }

    public function close(): Promise
    {
        $this->channel->close();
        return $this->whenClosed();
    }

    public function whenClosed(): Promise
    {
        return $this->channel->whenClosed();
    }

    /**
     * Once the session has been closed, this method will return the session result
     */
    public function getResult(): ?SessionResult
    {
        return $this->result;
    }

    // internal

    private $channel;
    private $exitStatus;
    private $exitReason;
    private $result;

    private function __construct(Channel $channel)
    {
        $this->channel = $channel;
        $channel->getOutput()->ignoreExtendedData($exceptFor = [1]);
        $channel->onRequestReceived(function (ChannelRequestHandle $handle) {
            switch ($handle->getRequestType()) {
                case 'exit-status':
                    if (!isset($this->exitStatus)) {
                        $this->exitStatus = \unpack('Nstatus', $handle->getRequest()->getTypeSpecificData())['status'];
                    }
                    break;

                case 'exit-signal':
                    if (!isset($this->exitReason)) {
                        $this->exitReason = ExitReason::fromBinaryData($handle->getRequest()->getTypeSpecificData());
                    }
                    break;
            }
        });
        $channel->whenClosed()->then(function () {
            $this->result = new SessionResult($this->exitStatus, $this->exitReason);
        });
    }

    private function init(SessionOpenRequest $configuration): \Generator
    {
        foreach ($configuration->getEnvChannelRequests() as $channelRequest) {
            $setEnvResult = yield $this->channel->sendRequest($channelRequest);
            if ($setEnvResult !== ChannelRequestResult::SUCCESS) {
                $this->channel->close();
                return SessionOpenResult::failure();
            }
        }

        $mainRequestResult = yield $this->channel->sendRequest($configuration->getMainChannelRequest());
        if ($mainRequestResult !== ChannelRequestResult::SUCCESS) {
            $this->channel->close();
            return SessionOpenResult::failure();
        }
    }
}
