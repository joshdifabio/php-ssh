<?php
namespace SSH2\Connection\Internal\Message;

use SSH2\Message;

class RequestSuccessMessage extends Message
{
    private $responseSpecificData;

    public function __construct(string $responseSpecificData)
    {
        $this->responseSpecificData = $responseSpecificData;

        $binary = \pack('C', ConnectionMessageNumber::REQUEST_SUCCESS) . $responseSpecificData;
        parent::__construct(ConnectionMessageNumber::REQUEST_SUCCESS, $binary);
    }

    public function getResponseSpecificData()
    {
        return $this->responseSpecificData;
    }
}
