<?php
namespace SSH2\Connection;

abstract class ChannelRequestResult
{
    const SUCCESS = 1;
    const FAILURE = 2;
    const CHANNEL_CLOSED = 3;
}
