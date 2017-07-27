<?php
namespace SSH2\Transport\KeyExchange;

use SSH2\Future;

class KeyExchangeAlgorithmInput
{
    private $ownIdentificationString;
    private $otherSidesIdentificationString;
    private $ownKexinitMessage;
    private $otherSidesKexinitMessage;
    private $negotiationResult;

    public function __construct(
        string $ownIdentificationString,
        KexinitMessage $ownKexinitMessage,
        AlgorithmNegotiationResult $negotiationResult
    ) {
        $this->ownIdentificationString = $ownIdentificationString;
        $this->ownKexinitMessage = $ownKexinitMessage;
        $this->negotiationResult = $negotiationResult;
        $this->otherSidesKexinitMessage = new Future;
        $this->otherSidesIdentificationString = new Future;
    }

    public function getOwnIdentificationString(): string
    {
        return $this->ownIdentificationString;
    }

    public function getOwnKexinitMessage(): KexinitMessage
    {
        return $this->ownKexinitMessage;
    }

    public function awaitOtherSidesIdentificationString(): Future
    {
        return $this->otherSidesIdentificationString;
    }

    public function awaitOtherSidesKexinitMessage(): Future
    {
        return $this->otherSidesKexinitMessage;
    }

    public function getNegotiationResult(): AlgorithmNegotiationResult
    {
        return $this->negotiationResult;
    }
}
