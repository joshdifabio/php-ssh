<?php
namespace SSH2\Transport;

interface WritableDataStream
{
    public function write(string $binaryData);
}
