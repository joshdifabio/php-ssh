<?php
namespace SSH2\Connection;

use SSH2\Connection\Internal\ChannelInputManager;
use SSH2\Connection\Internal\ChannelInputBuffer;
use SSH2\WritableDataBuffer;

class ChannelInput
{
    public function getStandardData(): WritableDataBuffer
    {
        return $this->standardDataBuffer;
    }

    public function getExtendedData(int $dataTypeCode): WritableDataBuffer
    {
        if (!isset($this->extendedDataBuffers[$dataTypeCode])) {
            if ($dataTypeCode < 1) {
                throw new \Error();
            }
            $this->extendedDataBuffers[$dataTypeCode] = new ChannelInputBuffer($this->inputBus, $dataTypeCode);
        }
        return $this->extendedDataBuffers[$dataTypeCode];
    }

    public function end()
    {
        $this->inputBus->end();
    }

    // internal

    private $inputBus;
    private $standardDataBuffer;
    private $extendedDataBuffers = [];

    /**
     * @internal
     */
    public function __construct(ChannelInputManager $inputBus)
    {
        $this->inputBus = $inputBus;
        $this->standardDataBuffer = new ChannelInputBuffer($inputBus);
    }
}
