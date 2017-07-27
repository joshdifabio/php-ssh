<?php
namespace SSH2\Authentication;

use SSH2\Rfc4250\MessageNumber;
use SSH2\Transport\Message\Message;
use SSH2\Transport\ClientSideTransportProtocol;
use SSH2\Transport\Message\ServiceRequestMessage;

class ClientSideAuthenticationProtocol
{
    const SERVICE_NAME = 'ssh-userauth';

    public static function start(ClientSideTransportProtocol $transport, ClientConfiguration $config): ClientSideAuthenticationProtocol
    {
        return new ClientSideAuthenticationProtocol($transport, $config);
    }

    public function sendMessage(Message $message): int
    {
        $messageNumber = $message->getMessageNumber();

        if ($messageNumber >= MessageNumber::AUTHENTICATION_MAX) {
            // todo: check that we are authd
        } elseif ($messageNumber === AuthenticationMessageNumber::USERAUTH_REQUEST) {
            // todo: send request
        } elseif ($messageNumber > MessageNumber::AUTHENTICATION_MIN) {
            throw new \Exception('The only authentication message which clients may send is SSH_MSG_USERAUTH_REQUEST.');
        }

        return $this->transport->sendMessage($message);
    }

    // internal stuff

    private $transport;
    private $config;

    private function __construct(ClientSideTransportProtocol $transport, ClientConfiguration $config)
    {
        $this->transport = $transport;
        $this->config = $config;
        $transport->setInboundMessageHandler(
            MessageNumber::AUTHENTICATION_MIN,
            MessageNumber::AUTHENTICATION_MAX,
            function () {

            }
        );
        $transport->sendMessage(new ServiceRequestMessage(self::SERVICE_NAME));
    }
}
