<?php
namespace SSH2;

interface WritableDataBuffer
{
    const WRITABLE = 1;
    const BLOCKED = 2;
    const ENDED = 3;
    const FINISHED = 4;

    public function write(string $data): int;

    public function end();

    public function wait(): Promise;

    public function whenFinished(): Promise;
}
