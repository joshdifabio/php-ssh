<?php
namespace SSH2\Transport;

interface ReadableDataStream
{
    public function read(int $length = PHP_INT_MAX): string;

    public function onReadable(callable $listener);
}
