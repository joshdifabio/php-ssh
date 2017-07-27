<?php
namespace SSH2\Rfc4250;

use SSH2\Authentication\AuthenticationMessageNumber;
use SSH2\Connection\ConnectionMessageNumber;
use SSH2\Transport\TransportMessageNumber;

interface MessageNumber extends TransportMessageNumber, AuthenticationMessageNumber, ConnectionMessageNumber
{
    const TRANSPORT_MIN = 1;
    const TRANSPORT_MAX = 49;

    const AUTHENTICATION_MIN = 50;
    const AUTHENTICATION_MAX = 79;
}
