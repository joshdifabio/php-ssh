<?php
namespace SSH2\Transport;

use SSH2\Transport\KeyExchange\KeyExchangeMethod;

class AlgorithmRegistry
{
    public function getKexAlgorithm(string $name): KeyExchangeMethod
    {

    }

    public function hasKexAlgorithm(string $name): bool
    {

    }

    public function getHostKeyAlgorithm(string $name): HostKeyAlgorithm
    {

    }

    public function hasHostKeyAlgorithm(string $name): bool
    {

    }

    public function getEncryptionAlgorithm(string $name): EncryptionAlgorithm
    {

    }

    public function hasEncryptionAlgorithm(string $name): bool
    {

    }

    public function getMacAlgorithm(string $name): MacAlgorithm
    {

    }

    public function hasMacAlgorithm(string $name): bool
    {

    }

    public function getCompressionAlgorithm(string $name): CompressionAlgorithm
    {

    }

    public function hasCompressionAlgorithm(string $name): bool
    {

    }
}
