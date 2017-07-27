<?php
namespace SSH2\Transport\Message;

use SSH2\Transport\TransportMessageNumber;

class UnimplementedMessage extends Message
{
    private $rejectedPacketSequenceNumber;

    public function __construct(int $rejectedPacketSequenceNumber)
    {
        $this->rejectedPacketSequenceNumber = $rejectedPacketSequenceNumber;

        $binary = \pack('CN', TransportMessageNumber::UNIMPLEMENTED, $rejectedPacketSequenceNumber);
        parent::__construct(TransportMessageNumber::UNIMPLEMENTED, $binary);
    }

    public function getRejectedPacketSequenceNumber(): int
    {
        return $this->rejectedPacketSequenceNumber;
    }
}
