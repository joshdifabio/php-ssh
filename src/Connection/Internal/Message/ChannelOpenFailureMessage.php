<?php
namespace SSH2\Connection\Internal\Message;

use SSH2\Connection\ChannelFailureReason;
use SSH2\Message;

class ChannelOpenFailureMessage extends Message implements ChannelMessage
{
    private $recipientChannel;
    private $failureReason;

    public function __construct(int $recipientChannel, ChannelFailureReason $failureReason)
    {
        $this->recipientChannel = $recipientChannel;
        $this->failureReason = $failureReason;

        if ($description = $failureReason->getDescription()) {
            $binary = \pack(
                'CNNNa*Na*',
                ConnectionMessageNumber::CHANNEL_OPEN_FAILURE,
                $recipientChannel,
                $failureReason->getReasonCode(),
                \strlen($description->getValue()),
                $description->getValue(),
                \strlen($description->getLanguageTag()),
                $description->getLanguageTag()
            );
        } else {
            $binary = \pack(
                'CNNNa*Na*',
                ConnectionMessageNumber::CHANNEL_OPEN_FAILURE,
                $recipientChannel,
                $failureReason->getReasonCode(),
                0,
                '',
                0,
                ''
            );
        }
        parent::__construct(ConnectionMessageNumber::CHANNEL_OPEN_FAILURE, $binary);
    }

    public function getRecipientChannel(): int
    {
        return $this->recipientChannel;
    }

    public function getFailureReason(): ChannelFailureReason
    {
        return $this->failureReason;
    }
}
