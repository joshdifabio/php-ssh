<?php
namespace SSH2\Connection\Internal\Message;

use SSH2\Connection\ChannelOptions;
use SSH2\Connection\ChannelOpenRequest;
use SSH2\Message;

class ChannelOpenMessage extends Message implements ChannelConfigurationMessage
{
    private $channelType;
    private $senderChannel;
    private $request;

    public function __construct(string $channelType, int $senderChannel, ChannelOpenRequest $request)
    {
        $this->channelType = $channelType;
        $this->senderChannel = $senderChannel;
        $this->request = $request;

        $binary = \pack(
            'CNa*NNN',
            ConnectionMessageNumber::CHANNEL_OPEN,
            \strlen($channelType),
            $channelType,
            $senderChannel,
            $request->getChannelOptions()->getInitialWindowSize(),
            $request->getChannelOptions()->getMaximumPacketSize()
        ) . $request->getData();
        parent::__construct(ConnectionMessageNumber::CHANNEL_OPEN, $binary);
    }

    public function getChannelType(): string
    {
        return $this->channelType;
    }

    public function getSenderChannel(): int
    {
        return $this->senderChannel;
    }

    public function getSenderConfiguration(): ChannelOptions
    {
        return $this->request->getChannelOptions();
    }
}
