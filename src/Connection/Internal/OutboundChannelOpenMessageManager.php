<?php
namespace SSH2\Connection\Internal;

use SSH2\Connection\Channel;
use SSH2\Connection\ChannelOpenRequest;
use SSH2\Connection\Internal\Message\ConnectionMessageNumber;
use SSH2\Connection\Internal\Message\ChannelOpenConfirmationMessage;
use SSH2\Connection\Internal\Message\ChannelOpenFailureMessage;
use SSH2\Connection\Internal\Message\ChannelOpenMessage;
use SSH2\Connection\ChannelOpenResult;
use SSH2\MessageProtocol;

/**
 * @internal
 */
class OutboundChannelOpenMessageManager
{
    private $underlyingProtocol;
    private $channels;
    private $pendingChannels = [];
    private $disconnected = false;

    public function __construct(
        MessageProtocol $underlyingProtocol,
        ChannelMessageRouter $messageRouter,
        ChannelSet $channels
    ) {
        $this->underlyingProtocol = $underlyingProtocol;
        $this->channels = $channels;
        $this->initObservers($messageRouter);
    }

    public function openChannel(ChannelOpenRequest $request, callable $resultHandler)
    {
        if ($this->disconnected) {
            $resultHandler(ChannelOpenResult::disconnect());
            return;
        }

        $localChannelId = $this->channels->nextLocalChannelId();
        $message = new ChannelOpenMessage($request->getChannelType(), $localChannelId, $request);
        $channel = new \stdClass();
        $channel->channelOpenMessage = $message;
        $channel->resultHandler = $resultHandler;
        $this->pendingChannels[$localChannelId] = $channel;
        $this->underlyingProtocol->send($message);
    }

    private function initObservers(ChannelMessageRouter $messageRouter)
    {
        $this->underlyingProtocol->onMessageReceived(
            ConnectionMessageNumber::CHANNEL_OPEN_CONFIRMATION,
            function (ChannelOpenConfirmationMessage $message) use ($messageRouter) {
                $localChannelId = $message->getRecipientChannel();
                $pendingChannel = $this->pendingChannels[$localChannelId] ?? null;
                if (!isset($pendingChannel)) {
                    return;
                }
                $channelMessageProtocol = new ChannelMessageProtocol($this->underlyingProtocol, $messageRouter, $localChannelId);
                $channel = new Channel($channelMessageProtocol, $pendingChannel->channelOpenMessage, $message);
                $result = ChannelOpenResult::success($channel);
                $this->channels->add($localChannelId, $channel);
                unset($this->pendingChannels[$localChannelId]);
                ($pendingChannel->resultHandler)($result);
            }
        );

        $this->underlyingProtocol->onMessageReceived(
            ConnectionMessageNumber::CHANNEL_OPEN_FAILURE,
            function (ChannelOpenFailureMessage $message) {
                $localChannelId = $message->getRecipientChannel();
                $pendingChannel = $this->pendingChannels[$localChannelId] ?? null;
                if (!isset($pendingChannel)) {
                    return;
                }
                $result = ChannelOpenResult::failure($message->getFailureReason());
                unset($this->pendingChannels[$localChannelId]);
                ($pendingChannel->resultHandler)($result);
            }
        );

        $this->underlyingProtocol->whenConnectionClosed()->then(function () {
            $this->disconnected = true;

            $pendingChannels = $this->pendingChannels;
            $this->pendingChannels = [];

            foreach ($pendingChannels as $pendingChannel) {
                ($pendingChannel->resultHandler)(ChannelOpenResult::disconnect());
            }
        });
    }
}
