<?php
namespace SSH2\Transport\KeyExchange;

use SSH2\Transport\AlgorithmRegistry;

class KexinitMessagePrototype
{
    use KexinitMessageTrait;

    public function createKexinitMessage(string $cookie): KexinitMessage
    {

    }

    public function referencesUnsupportedAlgorithms(AlgorithmRegistry $supportedAlgorithms): bool
    {
        foreach ($this->kexAlgorithms as $kexAlgoName) {
            if (!$supportedAlgorithms->hasKexAlgorithm($kexAlgoName)) {
                return true;
            }
        }

        foreach ($this->serverHostKeyAlgorithms as $hostKeyAlgoName) {
            if (!$supportedAlgorithms->hasHostKeyAlgorithm($hostKeyAlgoName)) {
                return true;
            }
        }

        foreach ($this->encryptionAlgorithmsClientToServer as $encryptionAlgoName) {
            if (!$supportedAlgorithms->hasEncryptionAlgorithm($encryptionAlgoName)) {
                return true;
            }
        }

        foreach ($this->encryptionAlgorithmsServerToClient as $encryptionAlgoName) {
            if (!$supportedAlgorithms->hasEncryptionAlgorithm($encryptionAlgoName)) {
                return true;
            }
        }

        foreach ($this->macAlgorithmsClientToServer as $macAlgoName) {
            if (!$supportedAlgorithms->hasMacAlgorithm($macAlgoName)) {
                return true;
            }
        }

        foreach ($this->macAlgorithmsServerToClient as $macAlgoName) {
            if (!$supportedAlgorithms->hasMacAlgorithm($macAlgoName)) {
                return true;
            }
        }

        foreach ($this->getCompressionAlgorithmsClientToServer() as $compressionAlgoName) {
            if (!$supportedAlgorithms->hasCompressionAlgorithm($compressionAlgoName)) {
                return true;
            }
        }

        foreach ($this->getCompressionAlgorithmsServerToClient() as $compressionAlgoName) {
            if (!$supportedAlgorithms->hasCompressionAlgorithm($compressionAlgoName)) {
                return true;
            }
        }

        return false;
    }
}
