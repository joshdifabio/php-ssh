<?php
namespace SSH2\Connection\Internal;

use SSH2\Connection\Internal\Message\ChannelMessage;
use SSH2\Connection\Internal\Message\ConnectionMessageNumber;
use SSH2\Internal\Observers;
use SSH2\MessageProtocol;
use SSH2\Subscription;
use SSH2\SubscriptionCollection;

/**
 * @internal
 */
class ChannelMessageRouter
{
    private $underlyingProtocol;
    private $subscriptions;
    private $observers = [];

    public function __construct(MessageProtocol $underlyingProtocol)
    {
        $this->underlyingProtocol = $underlyingProtocol;
        $this->subscriptions = new SubscriptionCollection();
        $this->init();
    }

    public function onMessageReceived(int $localChannelId, int $messageNumber, callable $observer): Subscription
    {
        if (!isset($this->observers[$messageNumber][$localChannelId])) {
            $this->observers[$messageNumber][$localChannelId] = new Observers();
        }

        /** @var Subscription $subscription */
        $subscription = $this->observers[$messageNumber][$localChannelId]->add($observer);
        $subscription->whenCancelled(function () use ($messageNumber, $localChannelId) {
            if ($this->observers[$messageNumber][$localChannelId]->count() == 0) {
                unset($this->observers[$messageNumber][$localChannelId]);
            }
        });
        $this->subscriptions->add($subscription);
        return $subscription;
    }

    private function init()
    {
        $supportedMessages = [
            ConnectionMessageNumber::CHANNEL_WINDOW_ADJUST,
            ConnectionMessageNumber::CHANNEL_DATA,
            ConnectionMessageNumber::CHANNEL_EXTENDED_DATA,
            ConnectionMessageNumber::CHANNEL_EOF,
            ConnectionMessageNumber::CHANNEL_CLOSE,
            ConnectionMessageNumber::CHANNEL_REQUEST,
            ConnectionMessageNumber::CHANNEL_SUCCESS,
            ConnectionMessageNumber::CHANNEL_FAILURE,
        ];

        foreach ($supportedMessages as $messageNumber) {
            $this->underlyingProtocol->onMessageReceived($messageNumber, function (ChannelMessage $message) use ($messageNumber) {
                $localChannelId = $message->getRecipientChannel();
                if (isset($this->observers[$messageNumber][$localChannelId])) {
                    $this->observers[$messageNumber][$localChannelId]->fire($message);
                }
            });
        }

        $this->underlyingProtocol->whenConnectionClosed()->then([$this->subscriptions, 'cancelAll']);
    }
}
