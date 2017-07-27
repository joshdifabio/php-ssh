<?php
namespace SSH2\Connection\Internal\Message;

class ConnectionMessageNumber
{
    const GLOBAL_REQUEST = 80;
    const REQUEST_SUCCESS = 81;
    const REQUEST_FAILURE = 82;
    const CHANNEL_OPEN = 90;
    const CHANNEL_OPEN_CONFIRMATION = 91;
    const CHANNEL_OPEN_FAILURE = 92;
    const CHANNEL_WINDOW_ADJUST = 93;
    const CHANNEL_DATA = 94;
    const CHANNEL_EXTENDED_DATA = 95;
    const CHANNEL_EOF = 96;
    const CHANNEL_CLOSE = 97;
    const CHANNEL_REQUEST = 98;
    const CHANNEL_SUCCESS = 99;
    const CHANNEL_FAILURE = 100;
}
