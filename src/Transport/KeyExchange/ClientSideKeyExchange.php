<?php
namespace SSH2\Transport\KeyExchange;

use SSH2\Transport\AlgorithmRegistry;
use SSH2\Transport\MessageProtocol;

final class ClientSideKeyExchange extends MessageProtocol
{
    public static function begin(
        ClientConfiguration $config,
        string $clientIdentificationString,
        string $serverIdentificationString
    ): MessageProtocol {
        return new ClientSideKeyExchange($config, $clientIdentificationString, $serverIdentificationString);
    }

    // internal

    private $config;
    private $clientIdentificationString;
    private $serverIdentificationString;

    private function __construct(
        ClientConfiguration $config,
        string $clientIdentificationString,
        string $serverIdentificationString
    ) {
        $this->config = $config;
        $this->clientIdentificationString = $clientIdentificationString;
        $this->serverIdentificationString = $serverIdentificationString;
        parent::__construct();
    }

    protected function run(): \Generator
    {
        $cookie = \random_bytes(16);
        $clientKexinitMsg = $this->config->getKexinitMessagePrototype()->createKexinitMessage($cookie);
        $this->sentMessageBuffer->write($clientKexinitMsg);

        if ($clientKexinitMsg->getFirstKexPacketFollows()) { // we (client) tried to guess the algorithms
            return yield from $this->guessAndRunKexAlgorithm($clientKexinitMsg, $this->config->getAlgorithms());
        }

        /** @var KexinitMessage $serverKexinitMsg */
        $serverKexinitMsg = yield from $this->awaitReceivedMessage(KexinitMessage::class);

        $negotiationResult = AlgorithmNegotiationResult::negotiate(
            $clientKexinitMsg,
            $serverKexinitMsg,
            $this->config->getAlgorithms()
        );

        if (!$negotiationResult->isSuccess()) {
            $this->sendDisconnectMessageDueToAlgoNegotiationFailure();
            return null;
        }

        $keyExchangeAlgoInput = new KeyExchangeAlgorithmInput(
            $this->clientIdentificationString,
            $this->serverIdentificationString,
            $clientKexinitMsg,
            $negotiationResult
        );
        $kexAlgorithm = $negotiationResult->getKexAlgorithm()->runClientSideAlgorithm($keyExchangeAlgoInput);

        // todo: implement checking of server kex method guess
        if ($serverKexinitMsg->getFirstKexPacketFollows()) {
            if (!$negotiationResult->isValidGuessFor()) {
                // the server guessed incorrectly so disregard the first message
                yield from $this->awaitReceivedMessage();
            }
        }

        return yield from $this->deferToMessageProtocol($kexAlgorithm);
    }

    private function guessAndRunKexAlgorithm(KexinitMessage $sentKexinitMsg, AlgorithmRegistry $algoRegistry): \Generator
    {
        $negotiationResult = AlgorithmNegotiationResult::guess($sentKexinitMsg, $algoRegistry);
        $preferredKexAlgo = $negotiationResult->getKexAlgorithm();
        $kexAlgorithmInput = $this->createKexAlgoInput($sentKexinitMsg, $negotiationResult);
        $kexAlgorithm = $preferredKexAlgo->runClientSideAlgorithm($kexAlgorithmInput);
        $firstKexMsgSent = $kexAlgorithm->getSentMessageBuffer()->awaitMessage();
        $this->sentMessageBuffer->write($firstKexMsgSent);
        /** @var KexinitMessage $receivedKexinitMsg */
        $receivedKexinitMsg = yield from $this->awaitReceivedMessage(KexinitMessage::class);

        if (!$negotiationResult->isValidGuessFor($receivedKexinitMsg)) {
            $negotiationResult = AlgorithmNegotiationResult::negotiate($sentKexinitMsg, $receivedKexinitMsg, $algoRegistry);
            if (!$negotiationResult->isSuccess()) {
                $this->sendDisconnectMessageDueToAlgoNegotiationFailure();
                return null;
            }
            if ($receivedKexinitMsg->getFirstKexPacketFollows()) {
                // the server guessed incorrectly so disregard the first message
                yield from $this->awaitReceivedMessage();
            }
            $kexAlgorithm = $negotiationResult->getKexAlgorithm()->runClientSideAlgorithm($negotiationResult);
        }

        return yield from $this->deferToMessageProtocol($kexAlgorithm);
    }

    private function sendDisconnectMessageDueToAlgoNegotiationFailure()
    {

    }

    private function createKexAlgoInput(
        KexinitMessage $clientKexinitMsg,
        AlgorithmNegotiationResult $negotiationResult
    ): KeyExchangeAlgorithmInput {
        return new KeyExchangeAlgorithmInput(
            $this->clientIdentificationString,
            $this->serverIdentificationString,
            $clientKexinitMsg,
            $negotiationResult
        );
    }
}
