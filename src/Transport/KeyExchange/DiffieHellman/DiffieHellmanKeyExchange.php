<?php
namespace SSH2\Transport\KeyExchange\DiffieHellman;

use phpseclib\Crypt\Hash;
use phpseclib\Math\BigInteger;
use SSH2\Transport\KeyExchange\KeyExchangeAlgorithmInput;
use SSH2\Transport\KeyExchange\KexinitMessage;
use SSH2\Transport\KeyExchange\KeyExchangeMethod;
use SSH2\Transport\KeyExchange\KeyExchangeOutput;
use SSH2\Transport\MessageProtocol;

class DiffieHellmanKeyExchange implements KeyExchangeMethod
{
    /**
     * @return KeyExchangeMethod[]
     */
    public static function getAllMethods(): array
    {
        return [
            DiffieHellmanKeyExchange::getGroup14Sha1Method(),
            DiffieHellmanKeyExchange::getGroup1Sha1Method(),
        ];
    }

    public static function getGroup1Sha1Method(): KeyExchangeMethod
    {
        return new DiffieHellmanKeyExchange(
            'diffie-hellman-group1-sha1',
            new BigInteger(
                'FFFFFFFFFFFFFFFFC90FDAA22168C234C4C6628B80DC1CD129024E088A67CC74' .
                '020BBEA63B139B22514A08798E3404DDEF9519B3CD3A431B302B0A6DF25F1437' .
                '4FE1356D6D51C245E485B576625E7EC6F44C42E9A637ED6B0BFF5CB6F406B7ED' .
                'EE386BFB5A899FA5AE9F24117C4B1FE649286651ECE65381FFFFFFFFFFFFFFFF',
                $base = 16
            ),
            new BigInteger(2),
            new Hash('sha1')
        );
    }

    public static function getGroup14Sha1Method(): KeyExchangeMethod
    {
        return new DiffieHellmanKeyExchange(
            'diffie-hellman-group14-sha1',
            new BigInteger(
                'FFFFFFFFFFFFFFFFC90FDAA22168C234C4C6628B80DC1CD129024E088A67CC74' .
                '020BBEA63B139B22514A08798E3404DDEF9519B3CD3A431B302B0A6DF25F1437' .
                '4FE1356D6D51C245E485B576625E7EC6F44C42E9A637ED6B0BFF5CB6F406B7ED' .
                'EE386BFB5A899FA5AE9F24117C4B1FE649286651ECE45B3DC2007CB8A163BF05' .
                '98DA48361C55D39A69163FA8FD24CF5F83655D23DCA3AD961C62F356208552BB' .
                '9ED529077096966D670C354E4ABC9804F1746C08CA18217C32905E462E36CE3B' .
                'E39E772C180E86039B2783A2EC07A28FB5C55DF06F4C52C9DE2BCBF695581718' .
                '3995497CEA956AE515D2261898FA051015728E5A8AACAA68FFFFFFFFFFFFFFFF',
                $base = 16
            ),
            new BigInteger(2),
            new Hash('sha1')
        );
    }

    public function runClientSideAlgorithm(KeyExchangeAlgorithmInput $input): MessageProtocol
    {
        return new class ($this->p, $this->g, $this->kexHash, $input) extends MessageProtocol
        {
            private $p;
            private $g;
            private $kexHash;
            private $input;

            public function __construct(
                BigInteger $p,
                BigInteger $g,
                Hash $kexHash,
                KeyExchangeAlgorithmInput $input
            ) {
                $this->p = $p;
                $this->g = $g;
                $this->kexHash = $kexHash;
                $this->input = $input;
                parent::__construct();
            }

            protected function runProtocol(): \Generator
            {
                /* To increase the speed of the key exchange, both client and server may
                reduce the size of their private exponents.  It should be at least
                twice as long as the key material that is generated from the shared
                secret.  For more details, see the paper by van Oorschot and Wiener
                [VAN-OORSCHOT].
                -- http://tools.ietf.org/html/rfc4419#section-6.2 */

                $one = new BigInteger(1);
                $negotiationResult = $this->input->getNegotiationResult();
                $encryptKeyLength = $negotiationResult->getEncryptionAlgorithmClientToServer()->getKeyLength();
                $decryptKeyLength = $negotiationResult->getEncryptionAlgorithmServerToClient()->getKeyLength();
                $keyLength = \min($this->kexHash->getLength(), \max($encryptKeyLength, $decryptKeyLength));
                $max = $one->bitwise_leftShift(16 * $keyLength)->subtract($one); // 2 * 8 * $keyLength
                $x = $one->random($one, $max);
                $e = $this->g->modPow($x, $this->p)->toBytes($twosCompliment = true);
                $kexdhInitMessage = new KexdhInitMessage($e);
                $this->sentMessageBuffer->writeMessage($kexdhInitMessage);
                /** @var KexinitMessage $serverKexinitMessage */
                $serverKexinitMessage = yield $this->input->awaitOtherSidesKexinitMessage();
                /** @var KexdhReplyMessage $kexdhReplyMessage */
                $kexdhReplyMessage = yield $this->receivedMessageBuffer->awaitMessage();

                $k = (new BigInteger($kexdhReplyMessage->getF(), -256))
                    ->modPow($x, $this->p)
                    ->toBytes($twosCompliment = true);

                $serverIdentificationString = yield $this->input->awaitOtherSidesIdentificationString();

                $h = $this->kexHash->hash(\pack(
                    'Na*Na*Na*Na*Na*a*Na*Na*Na*',
                    strlen($this->input->getOwnIdentificationString()),
                    $this->input->getOwnIdentificationString(),
                    strlen($serverIdentificationString),
                    $serverIdentificationString,
                    \strlen($this->input->getOwnKexinitMessage()->getPayload()),
                    $this->input->getOwnKexinitMessage()->getPayload(),
                    \strlen($serverKexinitMessage->getPayload()),
                    $serverKexinitMessage->getPayload(),
                    \strlen($kexdhReplyMessage->getServerPublicHostKeyAndCerts()),
                    $kexdhReplyMessage->getServerPublicHostKeyAndCerts(),
                    \strlen($e),
                    $e,
                    \strlen($kexdhReplyMessage->getF()),
                    $kexdhReplyMessage->getF(),
                    \strlen($k),
                    $k
                ));

                return new KeyExchangeOutput($k, $h);
            }
        };
    }

    public function runServerSideAlgorithm(KeyExchangeAlgorithmInput $input): MessageProtocol
    {
        return new class ($this->p, $this->g, $this->kexHash, $input) extends MessageProtocol
        {
            private $p;
            private $g;
            private $kexHash;
            private $input;

            public function __construct(
                BigInteger $p,
                BigInteger $g,
                Hash $kexHash,
                KeyExchangeAlgorithmInput $input
            ) {
                $this->p = $p;
                $this->g = $g;
                $this->kexHash = $kexHash;
                $this->input = $input;
                parent::__construct();
            }

            protected function runProtocol(): \Generator
            {
                /* To increase the speed of the key exchange, both client and server may
                reduce the size of their private exponents.  It should be at least
                twice as long as the key material that is generated from the shared
                secret.  For more details, see the paper by van Oorschot and Wiener
                [VAN-OORSCHOT].
                -- http://tools.ietf.org/html/rfc4419#section-6.2 */

                $one = new BigInteger(1);
                $negotiationResult = $this->input->getNegotiationResult();
                $encryptKeyLength = $negotiationResult->getEncryptionAlgorithmClientToServer()->getKeyLength();
                $decryptKeyLength = $negotiationResult->getEncryptionAlgorithmServerToClient()->getKeyLength();
                $keyLength = \min($this->kexHash->getLength(), \max($encryptKeyLength, $decryptKeyLength));
                $max = $one->bitwise_leftShift(16 * $keyLength)->subtract($one); // 2 * 8 * $keyLength
                $y = $one->random(new BigInteger(0), $max);
                $f = $this->g->modPow($y, $this->p)->toBytes($twosCompliment = true);
                /** @var KexinitMessage $clientKexinitMessage */
                $clientKexinitMessage = yield $this->input->awaitOtherSidesKexinitMessage();
                /** @var KexdhInitMessage $kexdhInitMessage */
                $kexdhInitMessage = yield $this->receivedMessageBuffer->awaitMessage();

                $k = (new BigInteger($kexdhInitMessage->getE(), -256))
                    ->modPow($y, $this->p)
                    ->toBytes($twosCompliment = true);

                $clientIdentificationString = yield $this->input->awaitOtherSidesIdentificationString();

                $h = $this->kexHash->hash(\pack(
                    'Na*Na*Na*Na*Na*a*Na*Na*Na*',
                    strlen($clientIdentificationString),
                    $clientIdentificationString,
                    strlen($this->input->getOwnIdentificationString()),
                    $this->input->getOwnIdentificationString(),
                    \strlen($clientKexinitMessage->getPayload()),
                    $clientKexinitMessage->getPayload(),
                    \strlen($this->input->getOwnKexinitMessage()->getPayload()),
                    $this->input->getOwnKexinitMessage()->getPayload(),
                    \strlen($kexdhInitMessage->getServerPublicHostKeyAndCerts()),
                    $kexdhInitMessage->getServerPublicHostKeyAndCerts(),
                    \strlen($f),
                    $f,
                    \strlen($kexdhInitMessage->getF()),
                    $kexdhInitMessage->getF(),
                    \strlen($k),
                    $k
                ));

                return new KeyExchangeOutput($k, $h);
            }
        };
    }

    public function getName(): string
    {
        return $this->methodName;
    }

    public function requiresEncryptionCapableHostKey(): bool
    {
        // TODO: Implement requiresEncryptionCapableHostKey() method.
    }

    public function requiresSignatureCapableHostKey(): bool
    {
        // TODO: Implement requiresSignatureCapableHostKey() method.
    }

    // internal

    private $methodName;
    private $p;
    private $g;
    private $kexHash;

    private function __construct(string $methodName, BigInteger $p, BigInteger $g, Hash $kexHash)
    {
        $this->methodName = $methodName;
        $this->p = $p;
        $this->g = $g;
        $this->kexHash = $kexHash;
    }
}
