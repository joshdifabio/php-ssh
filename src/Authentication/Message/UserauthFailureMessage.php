<?php
namespace SSH2\Authentication\Message;

use SSH2\Authentication\AuthenticationMessageNumber;
use SSH2\Transport\Message\Message;

class UserauthFailureMessage extends Message
{
    public function getAuthenticationsThatCanContinue(): array
    {

    }
}
