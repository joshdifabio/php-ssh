<?php
namespace SSH2\Connection\Internal\Message;

use SSH2\Connection\ChannelOptions;
use SSH2\Connection\ChannelConfirmation;
use SSH2\Message;

class ChannelOpenConfirmationMessage extends Message implements ChannelConfigurationMessage, ChannelMessage
{
    private $recipientChannel;
    private $senderChannel;
    private $confirmation;

    public function __construct(int $recipientChannel, int $senderChannel, ChannelConfirmation $request)
    {
        $this->recipientChannel = $recipientChannel;
        $this->senderChannel = $senderChannel;
        $this->confirmation = $request;

        $binary = \pack('CNNNN',
            ConnectionMessageNumber::CHANNEL_OPEN_CONFIRMATION,
            $recipientChannel,
            $senderChannel,
            $request->getChannelOptions()->getInitialWindowSize(),
            $request->getChannelOptions()->getMaximumPacketSize()
        ) . $request->getData();
        parent::__construct(ConnectionMessageNumber::CHANNEL_OPEN_CONFIRMATION, $binary);
    }

    public function getRecipientChannel(): int
    {
        return $this->recipientChannel;
    }

    public function getSenderChannel(): int
    {
        return $this->senderChannel;
    }

    public function getSenderConfiguration(): ChannelOptions
    {
        return $this->confirmation->getChannelOptions();
    }
}
