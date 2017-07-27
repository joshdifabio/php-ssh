<?php
namespace SSH2\Transport;

use SSH2\Transport\KeyExchange\KeyExchangeMessageNumber;

interface TransportMessageNumber extends KeyExchangeMessageNumber
{
    const DISCONNECT = 1;
    const IGNORE = 2;
    const UNIMPLEMENTED = 3;
    const DEBUG = 4;
    const SERVICE_REQUEST = 5;
    const SERVICE_ACCEPT = 6;
}
