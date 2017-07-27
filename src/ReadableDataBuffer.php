<?php
namespace SSH2;

interface ReadableDataBuffer
{
    const EMPTY = 1;
    const READABLE = 2;
    const ENDED = 3;    // the stream has ended; no more data shall be written to the buffer although it may contain data now

    public function readAll(): string;

    public function read(int $length): string;

    public function wait(): Promise;

    public function whenEnded(): Promise;
}
