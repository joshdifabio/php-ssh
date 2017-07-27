<?php
namespace SSH2\Authentication;

interface AuthenticationMethod
{
    public function handleReceivedMessage();
}
