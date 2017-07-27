<?php
namespace SSH2\Authentication;

interface AuthenticationMessageNumber
{
    const USERAUTH_REQUEST  = 50;
    const USERAUTH_FAILURE  = 51;
    const USERAUTH_SUCCESS  = 52;
    const USERAUTH_BANNER   = 53;
    const USERAUTH_PK_OK    = 60;
}
