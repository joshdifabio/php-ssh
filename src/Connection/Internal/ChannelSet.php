<?php
namespace SSH2\Connection\Internal;

use SSH2\Connection\Channel;

/**
 * @internal
 */
class ChannelSet
{
    private $nextLocalChannelId = 1;
    private $channels = [];

    public function add(int $localChannelId, Channel $channel)
    {
        if (isset($this->channels[$localChannelId])) {
            if ($channel !== $this->channels[$localChannelId]) {
                throw new \Error();
            }
        } else {
            $this->channels[$localChannelId] = $channel;
            $channel->whenClosed()->then(function () use ($localChannelId) {
                unset($this->channels[$localChannelId]);
            });
        }
    }

    public function get(int $localChannelId): ?Channel
    {
        return $this->channels[$localChannelId] ?? null;
    }

    /**
     * @return Channel[]
     */
    public function getAll(): array
    {
        return $this->channels;
    }

    public function nextLocalChannelId()
    {
        return $this->nextLocalChannelId++;
    }
}
