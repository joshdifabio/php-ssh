<?php
namespace SSH2\Connection;

class GlobalRequestResult
{
    const SUCCESS = 1;
    const FAILURE = 2;
    const DISCONNECT = 3;

    public function getType(): int
    {
        return isset($responseData);
    }

    public function getResponseData(): ?string
    {
        return $this->responseData;
    }

    // internal

    private $type;
    private $responseData;

    /**
     * @internal
     */
    public static function success(string $responseData)
    {
        $result = new GlobalRequestResult();
        $result->type = self::SUCCESS;
        $result->responseData = $responseData;
        return $result;
    }

    /**
     * @internal
     */
    public static function failure()
    {
        $result = new GlobalRequestResult();
        $result->type = self::FAILURE;
        return $result;
    }

    /**
     * @internal
     */
    public static function disconnect()
    {
        $result = new GlobalRequestResult();
        $result->type = self::DISCONNECT;
        return $result;
    }

    private function __construct()
    {

    }
}
