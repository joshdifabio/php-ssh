<?php
namespace SSH2\Transport;

use PHPUnit\Framework\TestCase;
use SSH2\Transport\Packet\InboundDataStream;
use SSH2\Transport\Packet\OutboundDataStream;

class PacketTest extends TestCase
{
    public function testUnparseParseSimple()
    {
        $outboundStream = new OutboundDataStream;
        $inboundStream = new InboundDataStream;
        $outboundStream->writeBinaryData("Hello world!\r\n");
        $outboundStream->writePacket('Foo!');
        $outboundStream->writePacket('Bar!');
        $inboundStream->write($outboundStream->read());
    }

    /**
     * @expectedException \SSH2\Transport\Packet\PacketParsingError
     */
    public function _testInvalidPacket()
    {
        $readableStream = new InboundDataStream;
        $readableStream->write(\str_repeat('x', 100));
    }
}
