<?php

namespace Algoritma\PhpInfo\Tests\Integration\Command;

use Algoritma\PhpInfo\Command\InspectPhpInfoCommand;
use Algoritma\PhpInfo\Service\PhpInfoFetcher;
use Algoritma\PhpInfo\Service\PhpInfoFilter;
use Algoritma\PhpInfo\Service\PhpInfoParser;
use Algoritma\PhpInfo\Service\PhpInfoRenderer;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversNothing]
class InspectPhpInfoEnvTest extends TestCase
{
    private CommandTester $commandTester;

    private MockObject $fetcher;

    private Stub $parser;

    private Stub $filter;

    private Stub $renderer;

    protected function setUp(): void
    {
        $this->fetcher = $this->createMock(PhpInfoFetcher::class);
        $this->parser = $this->createStub(PhpInfoParser::class);
        $this->filter = $this->createStub(PhpInfoFilter::class);
        $this->renderer = $this->createStub(PhpInfoRenderer::class);

        $command = new InspectPhpInfoCommand(
            $this->fetcher,
            $this->parser,
            $this->filter,
            $this->renderer
        );

        $this->commandTester = new CommandTester($command);
    }

    public function testExecuteWithEnvVariables(): void
    {
        putenv('APP_URL=http://env-url.local');
        putenv('APP_PUBLIC_DIR=/env/public/dir');

        $this->fetcher->expects($this->once())
            ->method('createTempFile')
            ->with('/env/public/dir')
            ->willReturn('/env/public/dir/tmp.php');

        $this->fetcher->expects($this->once())
            ->method('fetchPhpInfo')
            ->with('http://env-url.local/tmp.php')
            ->willReturn('<html></html>');

        $this->parser->method('parse')->willReturn(['Section' => []]);

        $exitCode = $this->commandTester->execute([]);

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('http://env-url.local', $this->commandTester->getDisplay());

        // Cleanup
        putenv('APP_URL');
        putenv('APP_PUBLIC_DIR');
    }

    public function testCliArgumentsOverrideEnvVariables(): void
    {
        putenv('APP_URL=http://env-url.local');
        putenv('APP_PUBLIC_DIR=/env/public/dir');

        $this->fetcher->expects($this->once())
            ->method('createTempFile')
            ->with('/cli/public/dir')
            ->willReturn('/cli/public/dir/tmp.php');

        $this->fetcher->expects($this->once())
            ->method('fetchPhpInfo')
            ->with('http://cli-url.local/tmp.php')
            ->willReturn('<html></html>');

        $this->parser->method('parse')->willReturn(['Section' => []]);

        $exitCode = $this->commandTester->execute([
            'base-url' => 'http://cli-url.local',
            'public-dir' => '/cli/public/dir',
        ]);

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('http://cli-url.local', $this->commandTester->getDisplay());

        // Cleanup
        putenv('APP_URL');
        putenv('APP_PUBLIC_DIR');
    }
}
