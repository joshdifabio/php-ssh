<?php
namespace SSH2\Connection;

use SSH2\ReadableDataBuffer;

interface ChannelOutput
{
    public function getStandardData(): ReadableDataBuffer;

    public function ignoreStandardData();

    public function getExtendedData(int $dataType): ReadableDataBuffer;

    public function ignoreExtendedData(array $exceptedDataTypes = []);

    public function setBufferSize(int $bufferSize);
}
