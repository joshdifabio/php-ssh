<?php
namespace SSH2\Transport\KeyExchange;

use SSH2\Transport\MessageProtocol;

interface KeyExchangeMethod
{
    public function runClientSideAlgorithm(KeyExchangeAlgorithmInput $input): MessageProtocol;

    public function runServerSideAlgorithm(KeyExchangeAlgorithmInput $input): MessageProtocol;

    public function getName(): string;

    public function requiresEncryptionCapableHostKey(): bool;

    public function requiresSignatureCapableHostKey(): bool;
}
