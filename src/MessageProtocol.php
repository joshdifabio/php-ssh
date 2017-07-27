<?php
namespace SSH2;

interface MessageProtocol
{
    const READY = 1;    // continue sending
    const BLOCKED = 2;  // wait for state to change to CONTINUE before attempting to send
    const ENDED = 3;    // sending has ended; no more message shall be sent

    /**
     * @return int  If MessageProtocol::BLOCKED then wait() should be called prior to calling send() again. If
     *              MessageProtocol::ENDED then no further messages shall be sent.
     */
    public function send(Message $message): int;

    /**
     * $observer shall be called as soon as the write state is not MessageProtocol::BLOCKED, and shall be passed either
     * MessageProtocol::READY or MessageProtocol::ENDED.
     */
    public function wait(): Promise;

    public function onMessageReceived(int $messageNumber, callable $observer): Subscription;

    public function whenConnectionClosed(): Promise;
}
