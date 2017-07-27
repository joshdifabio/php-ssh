<?php
namespace SSH2\Connection\PortForwarding;

use SSH2\Connection\Channel;
use SSH2\Connection\ChannelOpenResult;
use SSH2\Connection\ConnectionProtocol;
use SSH2\Internal\Coroutine;
use SSH2\Promise;
use SSH2\ReadableDataBuffer;
use SSH2\WritableDataBuffer;

class Tunnel
{
    /**
     * @see TunnelOpenResult
     */
    public static function open(ConnectionProtocol $connection, TunnelConfiguration $configuration): Promise
    {
        return Coroutine::run(function () use ($connection, $configuration) {
            /** @var ChannelOpenResult $channelOpenResult */
            $channelOpenResult = yield $connection->openChannel($configuration->getChannelOpenRequest());

            switch ($channelOpenResult->getType()) {
                case ChannelOpenResult::SUCCESS:
                    $channel = $channelOpenResult->getChannel();
                    $tunnel = new Tunnel($channel);
                    return TunnelOpenResult::success($tunnel);

                case ChannelOpenResult::FAILURE:
                    return TunnelOpenResult::failure();

                case ChannelOpenResult::DISCONNECT:
                    return TunnelOpenResult::disconnect();

                default:
                    throw new \Error();
            }
        });
    }

    public function getInput(): WritableDataBuffer
    {
        return $this->channel->getInput()->getStandardData();
    }

    public function getOutput(): ReadableDataBuffer
    {
        return $this->channel->getOutput()->getStandardData();
    }

    public function setOutputBufferSize(int $bufferSize)
    {
        $this->channel->getOutput()->setBufferSize($bufferSize);
    }

    public function close()
    {
        $this->channel->close();
    }

    public function whenClosed(): Promise
    {
        return $this->channel->whenClosed();
    }

    private $channel;

    private function __construct(Channel $channel)
    {
        $this->channel = $channel;
        $channel->getOutput()->ignoreExtendedData();
        $this->getInput()->whenFinished()->then([$channel->getInput(), 'end']);
    }
}
