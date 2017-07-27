<?php
namespace SSH2\Transport\KeyExchange;

class KeyExchangeOutput
{
    private $k;
    private $h;

    public function __construct(string $k, string $h)
    {
        $this->k = $k;
        $this->h = $h;
    }

    /**
     * Get shared secret K
     */
    public function getK(): string
    {
        return $this->k;
    }

    /**
     * Get exchange hash H
     */
    public function getH(): string
    {
        return $this->h;
    }

    public function equals($object): bool
    {
        return $object instanceof KeyExchangeOutput
            && $object->k === $this->k
            && $object->h === $this->h;
    }
}
