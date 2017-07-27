<?php
namespace SSH2\Transport\KeyExchange;

use SSH2\Transport\AlgorithmRegistry;
use SSH2\Transport\EncryptionAlgorithm;

class AlgorithmNegotiationResult
{
    public static function negotiate(
        KexinitMessage $clientMessage,
        KexinitMessage $serverMessage,
        AlgorithmRegistry $algorithmRegistry
    ): AlgorithmNegotiationResult {
        return new AlgorithmNegotiationResult($clientMessage, $serverMessage, $algorithmRegistry);
    }

    public static function guess(
        KexinitMessage $kexinitMessage,
        AlgorithmRegistry $algorithmRegistry
    ): AlgorithmNegotiationResult {

    }

    public function isSuccess(): bool
    {

    }

    public function isValidGuessFor(KexinitMessage $kexinitMessage): bool
    {

    }

    /**
     * @return null|KeyExchangeMethod
     */
    public function getKexAlgorithm()
    {
        return $this->kexAlgorithm;
    }

    /**
     * @return null|EncryptionAlgorithm
     */
    public function getEncryptionAlgorithmClientToServer()
    {

    }

    /**
     * @return null|EncryptionAlgorithm
     */
    public function getEncryptionAlgorithmServerToClient()
    {

    }

    // internal

    private $algorithms;
    private $client;
    private $server;
    private $kexAlgorithm;

    private function __construct(KexinitMessage $clientMessage, KexinitMessage $serverMessage)
    {
        $this->algorithms = $algorithms;
        $this->client = $client;
        $this->server = $server;

        foreach ($client->getKexAlgorithms() as $kexAlgoName) {
            if (!\in_array($kexAlgoName, $server->getKexAlgorithms(), $strict = true)) {
                continue;
            }

            $kexAlgo = $algorithms->getKexAlgorithm($kexAlgoName);

            if ($kexAlgo->requiresEncryptionCapableHostKey()) {
                if (null === $this->getEncryptionCapableHostKeyAlgo()) {
                    continue;
                }
            }

            if ($kexAlgo->requiresSignatureCapableHostKey()) {
                if (null === $this->getSignatureCapableHostKeyAlgo()) {
                    continue;
                }
            }

            $this->kexAlgorithm = $kexAlgo;
            break;
        }
    }

    private function getEncryptionCapableHostKeyAlgo()
    {
        foreach ($this->getHostKeyAlgosSupportedByBothSides() as $hostKeyAlgoName) {
            if ($this->algorithms->getHostKeyAlgorithm($hostKeyAlgoName)->isSignatureCapable()) {
                return $hostKeyAlgoName;
            }
        }

        return null;
    }

    private function getSignatureCapableHostKeyAlgo()
    {
        foreach ($this->getHostKeyAlgosSupportedByBothSides() as $hostKeyAlgoName) {
            if ($this->algorithms->getHostKeyAlgorithm($hostKeyAlgoName)->isSignatureCapable()) {
                return $hostKeyAlgoName;
            }
        }

        return null;
    }

    private function getHostKeyAlgosSupportedByBothSides()
    {
        foreach ($this->client->getServerHostKeyAlgorithms() as $hostKeyAlgoName) {
            if (\in_array($hostKeyAlgoName, $this->server->getServerHostKeyAlgorithms(), $strict = true)) {
                yield $hostKeyAlgoName;
            }
        }
    }
}
