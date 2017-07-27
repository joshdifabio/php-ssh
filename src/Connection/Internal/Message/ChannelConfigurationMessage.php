<?php
namespace SSH2\Connection\Internal\Message;

use SSH2\Connection\ChannelOptions;

interface ChannelConfigurationMessage
{
    public function getSenderChannel(): int;

    public function getSenderConfiguration(): ChannelOptions;
}
