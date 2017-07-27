<?php
namespace SSH2\Transport\KeyExchange\DiffieHellman;

interface DiffieHellmanMessageNumber
{
    const EXDH_GEX_REQUEST_OLD  = 30;
    const KEXDH_GEX_GROUP       = 31;
    const KEXDH_INIT            = 32;
    const KEXDH_REPLY           = 33;
    const KEXDH_GEX_REQUEST     = 34;
}
