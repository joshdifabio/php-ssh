<?php
namespace SSH2\Transport\Message;

use SSH2\Transport\TransportMessageNumber;

class IgnoreMessage extends Message
{
    private $data;

    public function __construct(string $data)
    {
        $this->data = $data;

        $binary = \pack('CNa*', TransportMessageNumber::IGNORE, \strlen($data), $data);
        parent::__construct(TransportMessageNumber::IGNORE, $binary);
    }

    public function getData(): string
    {
        return $this->data;
    }
}
