<?php
namespace SSH2;

class SubscriptionCollection
{
    private $nextSubscriptionId = 'a';
    /** @var Subscription[] */
    private $subscriptions = [];

    public function add(Subscription $subscription)
    {
        $subscriptionId = $this->nextSubscriptionId++;
        $this->subscriptions[$subscriptionId] = $subscription;
        $subscription->whenCancelled(function () use ($subscriptionId) {
            unset($this->subscriptions[$subscriptionId]);
        });
    }

    public function cancelAll()
    {
        foreach ($this->subscriptions as $subscription) {
            $subscription->cancel();
        }
    }
}
