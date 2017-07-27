<?php
namespace SSH2\Internal;

use SSH2\Message;
use SSH2\Subscription;

class MessageBus
{
    /** @var Observers[] */
    private $observers = [];

    public function handleMessage(Message $message): bool
    {
        $messageNumber = $message->getMessageNumber();

        if (!isset($this->observers[$messageNumber]) || $this->observers[$messageNumber]->count() == 0) {
            return false;
        }

        $this->observers[$messageNumber]->fire($message);
        return true;
    }

    public function addHandler(int $messageNumber, callable $handler): Subscription
    {
        if (!isset($this->observers[$messageNumber])) {
            $this->observers[$messageNumber] = new Observers();
        }

        return $this->observers[$messageNumber]->add($handler);
    }
}
