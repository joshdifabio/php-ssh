<?php
namespace SSH2\Transport\Packet;

use SSH2\Future;
use SSH2\Transport\WritableDataStream;

class InboundDataStream implements WritableDataStream
{
    private $parser;
    private $identificationString;
    private $receivedPackets = [];
    /** @var Future[] */
    private $receivedPacketFutures = [];
    private $error;

    private $decompressionAlgorithm;
    private $macAlgorithm;
    private $macLength;
    private $decryptionAlgorithm;
    private $blockSize = 8;

    public function __construct()
    {
        $this->identificationString = new Future;
        $this->parser = $this->createParser();
    }

    public function awaitIdentificationString(): Future
    {
        return $this->identificationString;
    }

    public function awaitPacket(): Future
    {
        $future = new Future;

        if (!empty($this->receivedPackets)) {
            $future->succeed(\array_shift($this->receivedPackets));
        } elseif (isset($this->error)) {
            throw $this->error;
        } else {
            $this->receivedPacketFutures[] = $future;
        }

        return $future;
    }

    public function write(string $binaryData)
    {
        if (isset($this->error)) {
            throw $this->error;
        }

        try {
            $this->parser->send($binaryData);
        } catch (\Throwable $e) {
            $this->error = $e;
            throw $e;
        }
    }

    public function setMacAlgorithm(callable $algorithm, int $macLength)
    {
        if ($macLength < 1) {
            throw new \Exception;
        }

        $this->macAlgorithm = $algorithm;
        $this->macLength = $macLength;
    }

    public function setDecryptionAlgorithm(callable $algorithm, int $blockSize)
    {
        if ($blockSize < 1) {
            throw new \Exception;
        }

        $this->decryptionAlgorithm = $algorithm;
        $this->blockSize = \max(8, $blockSize);
    }

    public function setDecompressionAlgorithm(callable $algorithm)
    {
        $this->decompressionAlgorithm = $algorithm;
    }

    private function createParser(): \Generator
    {
        $identificationString = '';

        while (true) {
            $receivedData = yield;
            $nrBytesReceived = \strlen($receivedData);
            for ($offset = 0; $offset < $nrBytesReceived; $offset++) {
                if ($receivedData{$offset} === "\n") {
                    $identificationString .= \substr($receivedData, 0, $offset + 1);
                    $rawDataBuffer = \substr($receivedData, $offset + 1);
                    break(2);
                }
            }
        }

        try {
            $cleanedIdentificationString = $this->cleanIdentificationString($identificationString);
        } catch (\Throwable $e) {
            $this->identificationString->fail($e);
            throw $e;
        }

        $this->identificationString->succeed($cleanedIdentificationString);

        $sequenceNo = 0;
        while (true) {
            while (\strlen($rawDataBuffer) < $this->blockSize) {
                $rawDataBuffer .= yield;
            }

            $firstBlock = \substr($rawDataBuffer, 0, $this->blockSize);

            if (isset($this->decryptionAlgorithm)) {
                $firstBlock = ($this->decryptionAlgorithm)($firstBlock);
            }

            $lengths = \unpack('Npacket_length/Cpadding_length', \substr($firstBlock, 0, 5));
            $packetLength = $lengths['packet_length'];
            $paddingLength = $lengths['padding_length'];
            $totalPacketLength = $packetLength + 4;
            $remainingPacketLength = $totalPacketLength - $this->blockSize;

            // quoting <http://tools.ietf.org/html/rfc4253#section-6.1>,
            // "implementations SHOULD check that the packet length is reasonable"
            // PuTTY uses 0x9000 as the actual max packet size and so to shall we
            // todo: understand why phpseclib had $remainingLength < -$this->blockSize which seems pointless
            if ($remainingPacketLength % $this->blockSize != 0) {
                throw new PacketParsingError('Packet is malformed.');
            }
            if ($remainingPacketLength > 0x9000) {
                throw new PacketParsingError('Packet is too long.');
            }

            while (\strlen($rawDataBuffer) < $totalPacketLength) {
                $rawDataBuffer .= yield;
            }

            if (isset($this->decryptionAlgorithm)) {
                $remainingEncryptedData = \substr($rawDataBuffer, $this->blockSize, $remainingPacketLength);
                $payloadAndPadding = \substr($firstBlock, 5) . ($this->decryptionAlgorithm)($remainingEncryptedData);
            } else {
                $payloadAndPadding = \substr($rawDataBuffer, 5, $packetLength - 1);
            }

            if (isset($this->macAlgorithm)) {
                while (\strlen($rawDataBuffer) < $totalPacketLength + $this->macLength) {
                    $rawDataBuffer .= yield;
                }

                $mac = \substr($rawDataBuffer, $totalPacketLength, $this->macLength);
                $packetData = \pack('NNCa*', $sequenceNo, $packetLength, $paddingLength, $payloadAndPadding);
                if ($mac !== ($this->macAlgorithm)($packetData)) {
                    throw new PacketParsingError('Packet has invalid MAC.');
                }

                $totalPacketLength += $this->macLength;
            }

            $payload = \substr($payloadAndPadding, 0, $packetLength - $paddingLength - 1);

            if ($this->decompressionAlgorithm) {
                $payload = ($this->decompressionAlgorithm)($payload);
            }

            $rawDataBuffer = \substr($rawDataBuffer, $totalPacketLength);
            $sequenceNo++;

            if (!empty($this->receivedPacketFutures)) {
                $futures = $this->receivedPacketFutures;
                $this->receivedPacketFutures = [];
                foreach ($futures as $future) {
                    $future->succeed($payload);
                }
            } else {
                $this->receivedPackets[] = $payload;
            }
        }
    }

    private function cleanIdentificationString(string $identificationString)
    {
        if (\substr($identificationString, -2) !== "\r\n") {
            throw new \Exception('Invalid identification string received.');
        }

        return \substr($identificationString, 0, -2);
    }
}
