<?php
namespace SSH2\Transport\Packet;

class PacketUnparser
{
    private $compressionAlgorithm;
    private $macAlgorithm;
    private $encryptionAlgorithm;
    private $blockSize = 8;

    public function unparsePacket(string $payload, int $sequenceNumber): string
    {
        if ($this->compressionAlgorithm) {
            $payload = ($this->compressionAlgorithm)($payload);
        }

        $payloadLength = \strlen($payload);
        // 4 (packet length) + 1 (padding length) + 4 (minimal padding amount) == 9
        $packetLength = $payloadLength + 9;
        // round up to the nearest $this->encrypt_block_size
        $packetLength += (($this->blockSize - 1) * $packetLength) % $this->blockSize;
        // subtracting strlen($payload) is obvious - subtracting 5 is necessary because of packetLength and paddingLength
        $paddingLength = $packetLength - $payloadLength - 5;
        $padding = \random_bytes($paddingLength);

        // we subtract 4 from packetLength because the packetLength field isn't supposed to include itself
        $packet = \pack('NCa*', $packetLength - 4, $paddingLength, $payload . $padding);

        if (isset($this->macAlgorithm)) {
            $mac = ($this->macAlgorithm)(\pack('Na*', $sequenceNumber, $packet));
        } else {
            $mac = '';
        }

        if (isset($this->encryptionAlgorithm)) {
            $packet = ($this->encryptionAlgorithm)($packet);
        }

        return $packet . $mac;
    }

    public function withMacAlgorithm(callable $algorithm): PacketUnparser
    {
        $copy = clone $this;
        $copy->macAlgorithm = $algorithm;
        return $copy;
    }

    public function withEncryptionAlgorithm(callable $algorithm, int $blockSize): PacketUnparser
    {
        if ($blockSize < 1) {
            throw new \Exception;
        }

        $copy = clone $this;
        $copy->encryptionAlgorithm = $algorithm;
        $copy->blockSize = \max(8, $blockSize);
        return $copy;
    }

    public function withCompressionAlgorithm(callable $algorithm): PacketUnparser
    {
        $copy = clone $this;
        $copy->compressionAlgorithm = $algorithm;
        return $copy;
    }
}
