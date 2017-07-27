<?php
namespace SSH2\Authentication\Message;

use SSH2\Authentication\AuthenticationMessageNumber;
use SSH2\Transport\Message\Message;

class UserauthSuccessMessage extends Message
{
    public function __construct()
    {
        $binary = \pack('C', AuthenticationMessageNumber::USERAUTH_SUCCESS);
        parent::__construct(AuthenticationMessageNumber::USERAUTH_SUCCESS, $binary);
    }
}
