<?php
namespace SSH2\Tests\Connection;

use PHPUnit\Framework\TestCase;
use SSH2\Connection\Channel;
use SSH2\Connection\ChannelConfirmation;
use SSH2\Connection\ChannelOpenRequest;
use SSH2\Connection\ChannelOpenRequestHandle;
use SSH2\Connection\ChannelOpenResult;
use SSH2\Connection\ChannelOptions;
use SSH2\Connection\ConnectionProtocol;
use SSH2\Internal\Coroutine;
use SSH2\ReadableDataBuffer;
use SSH2\Tests\DuplexMessageProtocol;
use SSH2\WritableDataBuffer;

class ConnectionProtocolTest extends TestCase
{
    public function testChannelLifecycle()
    {
        $this->runCoroutine(function () {
            /** @var Channel $serverSide */
            /** @var Channel $clientSide */
            list($serverSide, $clientSide) = yield from $this->openChannel(ChannelOptions::create(), ChannelOptions::create()->withInitialWindowSize(0));

            self::assertNotNull($serverSide);
            self::assertNotNull($clientSide);

            self::assertSame(WritableDataBuffer::BLOCKED, $serverSide->getInput()->getStandardData()->write('Hello client'));
            $serverSide->getInput()->getStandardData()->end();
            self::assertSame(WritableDataBuffer::ENDED, yield $serverSide->getInput()->getStandardData()->wait());
            $serverSide->getInput()->getStandardData()->whenFinished()->then([$serverSide->getInput(), 'end']);
            self::assertSame(WritableDataBuffer::WRITABLE, $clientSide->getInput()->getStandardData()->write('Hello server'));
            self::assertSame('', $clientSide->getOutput()->getStandardData()->readAll());
            $clientSide->getOutput()->setBufferSize(32 * 1024);
            $clientStandardOutputEnded = false;
            $clientSide->getOutput()->getStandardData()->whenEnded()->then(function () use (&$clientStandardOutputEnded) {
                $clientStandardOutputEnded = true;
            });
            self::assertTrue($clientStandardOutputEnded);
            self::assertSame('Hello client', $clientSide->getOutput()->getStandardData()->readAll());
            self::assertSame('Hello server', $serverSide->getOutput()->getStandardData()->readAll());

            self::assertSame(ReadableDataBuffer::ENDED, yield $clientSide->getOutput()->getStandardData()->wait());
            self::assertSame(WritableDataBuffer::FINISHED, yield $serverSide->getInput()->getStandardData()->wait());
            //self::assertSame(ReadableDataBuffer::EMPTY, $serverSide->getOutput()->getStandardData()->getState());

            self::assertSame(Channel::OPEN, $clientSide->getState());
            self::assertSame(Channel::OPEN, $serverSide->getState());
            $clientSide->close();
            self::assertSame(Channel::CLOSED, $clientSide->getState());
            self::assertSame(Channel::CLOSED, $serverSide->getState());

            self::assertSame(ReadableDataBuffer::ENDED, yield $serverSide->getOutput()->getStandardData()->wait());
            self::assertSame(WritableDataBuffer::FINISHED, yield $clientSide->getInput()->getStandardData()->wait());
        });
    }

    public function testWindowSpaceConsumption()
    {
        $this->runCoroutine(function () {
            /** @var Channel $serverSide */
            /** @var Channel $clientSide */
            list($serverSide, $clientSide) = yield from $this->openChannel(
                ChannelOptions::create()->withInitialWindowSize(100),
                ChannelOptions::create()->withInitialWindowSize(50)
            );

            $data = '';
            for ($n = 0; $n < 1000; $n++) {
                $data .= $n;
            }

            $serverSide->getInput()->getStandardData()->write($data);
            $serverSide->getInput()->getExtendedData(1)->write($data);
            $clientSide->getInput()->getStandardData()->write($data);
            $clientSide->getInput()->getExtendedData(1)->write($data);

            $nrChunks = \ceil(\strlen($data) / 46);

            for ($chunkNo = 0; $chunkNo < $nrChunks - 1; $chunkNo++) {
                self::assertSame('', $clientSide->getOutput()->getExtendedData(1)->readAll());
                $offset = $chunkNo * 46;
                self::assertSame(\substr($data, $offset, 46), $clientSide->getOutput()->getStandardData()->readAll());
            }
            $offset = ($nrChunks - 1) * 46;
            $lastStandardDataChunk = \substr($data, $offset, 46);
            self::assertSame($lastStandardDataChunk, $clientSide->getOutput()->getStandardData()->readAll());

            for ($chunkNo = 0; $chunkNo < $nrChunks; $chunkNo++) {
                self::assertSame('', $clientSide->getOutput()->getStandardData()->readAll());
                $offset = $chunkNo * 46;
                self::assertSame(\substr($data, $offset, 46), $clientSide->getOutput()->getExtendedData(1)->readAll());
            }
        });
    }

    public function testIgnoredExtendedData()
    {
        $this->runCoroutine(function () {
            /** @var Channel $serverSide */
            /** @var Channel $clientSide */
            list($serverSide, $clientSide) = yield from $this->openChannel(
                ChannelOptions::create()->withInitialWindowSize(100),
                ChannelOptions::create()->withInitialWindowSize(50)
            );

            $data = '';
            for ($n = 0; $n < 1000; $n++) {
                $data .= $n;
            }

            $clientSide->getOutput()->ignoreExtendedData();

            $serverSide->getInput()->getStandardData()->write($data);
            $serverSide->getInput()->getExtendedData(1)->write($data);
            $clientSide->getInput()->getStandardData()->write($data);
            $clientSide->getInput()->getExtendedData(1)->write($data);

            $nrChunks = \ceil(\strlen($data) / 46);

            for ($chunkNo = 0; $chunkNo < $nrChunks - 1; $chunkNo++) {
                self::assertSame('', $clientSide->getOutput()->getExtendedData(1)->readAll());
                $offset = $chunkNo * 46;
                self::assertSame(\substr($data, $offset, 46), $clientSide->getOutput()->getStandardData()->readAll());
            }
            $offset = ($nrChunks - 1) * 46;
            $lastStandardDataChunk = \substr($data, $offset, 46);
            self::assertSame($lastStandardDataChunk, $clientSide->getOutput()->getStandardData()->readAll());

            for ($chunkNo = 0; $chunkNo < $nrChunks; $chunkNo++) {
                self::assertSame('', $clientSide->getOutput()->getStandardData()->readAll());
                $offset = $chunkNo * 46;
                self::assertSame(\substr($data, $offset, 46), $clientSide->getOutput()->getExtendedData(1)->readAll());
            }
        });
    }

    private function openChannel(ChannelOptions $serverOptions, ChannelOptions $clientOptions)
    {
        $duplexMessageProtocol = new DuplexMessageProtocol();
        $server = new ConnectionProtocol($duplexMessageProtocol->getServer());
        $client = new ConnectionProtocol($duplexMessageProtocol->getClient());

        /** @var null|Channel $serverSide */
        $serverSide = null;
        /** @var null|Channel $clientSide */
        $clientSide = null;

        $server->onChannelOpenRequestReceived(function (ChannelOpenRequestHandle $handle) use ($serverOptions, &$serverSide) {
            $serverSide = $handle->confirm(ChannelConfirmation::create()->withChannelOptions($serverOptions));
        });

        /** @var ChannelOpenResult $channelOpenResult */
        $channelOpenResult = yield $client->openChannel(ChannelOpenRequest::ofType('foo')->withChannelOptions($clientOptions));
        $clientSide = $channelOpenResult->getChannel();

        self::assertNotNull($serverSide);
        self::assertNotNull($clientSide);

        return [$serverSide, $clientSide];
    }

    private function runCoroutine(callable $coroutineFn)
    {
        $result = Coroutine::run(function () use ($coroutineFn, &$error) {
            try {
                yield from $coroutineFn();
            } catch (\Throwable $error) {}
        });

        if ($error) {
            throw $error;
        }

        self::assertTrue($result->isResolved());
    }
}
