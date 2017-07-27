<?php
namespace SSH2\Transport\Packet;

use SSH2\Transport\ReadableDataStream;

class OutboundDataStream implements ReadableDataStream
{
    private $unparser;
    private $listeners = [];
    private $bufferedData = '';
    private $nextSequenceNo = 0;
    private $isNotifyingListeners = false;

    public function __construct()
    {
        $this->unparser = new PacketUnparser;
    }

    public function writePacket(string $payload): int
    {
        $wasEmpty = $this->bufferedData === '';
        $this->bufferedData .= $this->unparser->unparsePacket($payload, $this->nextSequenceNo);
        $sequenceNo = $this->nextSequenceNo++;
        if ($wasEmpty) {
            $this->notifyListeners();
        }
        return $sequenceNo;
    }

    public function writeBinaryData(string $binaryData)
    {
        if ($binaryData === '') {
            return;
        }

        $wasEmpty = $this->bufferedData === '';
        $this->bufferedData .= $binaryData;
        if ($wasEmpty) {
            $this->notifyListeners();
        }
    }

    /**
     * Read and remove binary data from the buffer
     */
    public function read(int $length = PHP_INT_MAX): string
    {
        if ($length >= \strlen($this->bufferedData)) {
            $data = $this->bufferedData;
            $this->bufferedData = '';
            return $data;
        }

        $data = \substr($this->bufferedData, 0, $length);
        $this->bufferedData = \substr($this->bufferedData, $length);
        return $data;
    }

    public function onReadable(callable $listener)
    {
        /*
         * fire the listener before adding it to $this->listeners[] to prevent possible overlapping calls
         * to $listener in the case that write() is called before $listener() returns
         */
        if ($this->bufferedData !== '') {
            $listener();
        }

        $this->listeners[] = $listener;
    }

    public function setMacAlgorithm(callable $algorithm)
    {
        $this->unparser = $this->unparser->withMacAlgorithm($algorithm);
    }

    public function setEncryptionAlgorithm(callable $algorithm, int $blockSize)
    {
        $this->unparser = $this->unparser->withEncryptionAlgorithm($algorithm, $blockSize);
    }

    public function setCompressionAlgorithm(callable $algorithm)
    {
        $this->unparser = $this->unparser->withCompressionAlgorithm($algorithm);
    }

    private function notifyListeners()
    {
        if ($this->isNotifyingListeners) {
            return;
        }

        $this->isNotifyingListeners = true;
        try {
            foreach ($this->listeners as $listener) {
                if ($this->bufferedData !== '') {
                    $listener();
                }
            }
        } finally {
            $this->isNotifyingListeners = false;
        }
    }
}
