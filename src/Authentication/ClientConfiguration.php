<?php
namespace SSH2\Authentication;

class ClientConfiguration
{
    public function getFlags(): array
    {
        return \array_keys($this->flags);
    }

    public function hasFlag(string $name): bool
    {
        return $this->flags[$name] ?? false;
    }

    public function withFlag(string $name): ClientConfiguration
    {
        if (isset($this->flags[$name])) {
            return $this;
        }

        $copy = clone $this;
        $copy->flags[$name] = true;
        return $copy;
    }

    public function withoutFlag(string $name): ClientConfiguration
    {
        if (!isset($this->flags[$name])) {
            return $this;
        }

        $copy = clone $this;
        unset($copy->flags[$name]);
        return $copy;
    }

    // internals

    private $sessionIdentifier;
    private $flags = [
        self::LOWER_LEVEL_PROTOCOL_PROVIDES_CONFIDENTIALITY => true,
    ];
}
