<?php

namespace Algoritma\PhpInfo\Tests\Command;

use Algoritma\PhpInfo\Command\InspectPhpInfoCommand;
use Algoritma\PhpInfo\Service\PhpInfoFetcher;
use Algoritma\PhpInfo\Service\PhpInfoFilter;
use Algoritma\PhpInfo\Service\PhpInfoParser;
use Algoritma\PhpInfo\Service\PhpInfoRenderer;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversNothing]
class InspectPhpInfoCommandTest extends TestCase
{
    private CommandTester $commandTester;

    private PhpInfoFetcher&Stub $fetcher;

    private PhpInfoParser&Stub $parser;

    private PhpInfoFilter&Stub $filter;

    private PhpInfoRenderer&Stub $renderer;

    protected function setUp(): void
    {
        $this->fetcher = $this->createStub(PhpInfoFetcher::class);
        $this->parser = $this->createStub(PhpInfoParser::class);
        $this->filter = $this->createStub(PhpInfoFilter::class);
        $this->renderer = $this->createStub(PhpInfoRenderer::class);

        $command = new InspectPhpInfoCommand(
            $this->fetcher,
            $this->parser,
            $this->filter,
            $this->renderer
        );

        $application = new Application();
        $application->addCommand($command);

        $command = $application->find('php:info');
        $this->commandTester = new CommandTester($command);
    }

    public function testExecuteFailsWithoutArguments(): void
    {
        $exitCode = $this->commandTester->execute([]);
        $this->assertEquals(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('The "base-url" argument is required', $this->commandTester->getDisplay());
    }

    public function testExecuteSuccess(): void
    {
        /** @var PhpInfoFetcher&MockObject $fetcher */
        $fetcher = $this->createMock(PhpInfoFetcher::class);
        /** @var PhpInfoParser&MockObject $parser */
        $parser = $this->createMock(PhpInfoParser::class);
        /** @var PhpInfoRenderer&MockObject $renderer */
        $renderer = $this->createMock(PhpInfoRenderer::class);

        $command = new InspectPhpInfoCommand(
            $fetcher,
            $parser,
            $this->filter,
            $renderer
        );
        $this->commandTester = new CommandTester($command);

        $baseUrl = 'http://localhost';
        $publicDir = '/tmp';
        $tmpFile = tempnam(sys_get_temp_dir(), 'phpinfo');
        $html = '<html><body><h1>PHP Version 8.4</h1></body></html>';
        $parsedData = ['PHP Version' => ['PHP Version' => ['local' => '8.4', 'default' => null]]];

        $fetcher->expects($this->once())
            ->method('createTempFile')
            ->willReturn($tmpFile);

        $fetcher->expects($this->once())
            ->method('fetchPhpInfo')
            ->willReturn($html);

        $parser->expects($this->once())
            ->method('parse')
            ->willReturn($parsedData);

        $renderer->expects($this->once())
            ->method('render');

        $exitCode = $this->commandTester->execute([
            'base-url' => $baseUrl,
            'public-dir' => $publicDir,
        ]);

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('PHP Info', $this->commandTester->getDisplay());
        $this->assertStringContainsString('Temp file deleted', $this->commandTester->getDisplay());
    }

    public function testExecuteWithSectionFilter(): void
    {
        $parsedData = ['Core' => [], 'opcache' => []];
        $filteredData = ['Core' => []];

        /** @var PhpInfoFilter&MockObject $filter */
        $filter = $this->createMock(PhpInfoFilter::class);
        /** @var PhpInfoRenderer&MockObject $renderer */
        $renderer = $this->createMock(PhpInfoRenderer::class);

        $command = new InspectPhpInfoCommand(
            $this->fetcher,
            $this->parser,
            $filter,
            $renderer
        );
        $this->commandTester = new CommandTester($command);

        $this->fetcher->method('createTempFile')->willReturn('/tmp/file.php');
        $this->fetcher->method('fetchPhpInfo')->willReturn('html');
        $this->parser->method('parse')->willReturn($parsedData);

        $filter->expects($this->once())
            ->method('filterSections')
            ->with($parsedData, ['core'])
            ->willReturn($filteredData);

        $renderer->expects($this->once())
            ->method('render');

        $this->commandTester->execute([
            'base-url' => 'http://localhost',
            '--section' => ['core'],
        ]);
    }

    public function testExecuteWithSearchOption(): void
    {
        $parsedData = ['Core' => ['memory_limit' => []]];
        $filteredData = ['Core' => ['memory_limit' => []]];

        /** @var PhpInfoFilter&MockObject $filter */
        $filter = $this->createMock(PhpInfoFilter::class);

        $command = new InspectPhpInfoCommand(
            $this->fetcher,
            $this->parser,
            $filter,
            $this->renderer
        );
        $this->commandTester = new CommandTester($command);

        $this->fetcher->method('createTempFile')->willReturn('/tmp/file.php');
        $this->fetcher->method('fetchPhpInfo')->willReturn('html');
        $this->parser->method('parse')->willReturn($parsedData);

        $filter->expects($this->once())
            ->method('filterSearch')
            ->with($parsedData, 'memory')
            ->willReturn($filteredData);

        $this->commandTester->execute([
            'base-url' => 'http://localhost',
            '--search' => 'memory',
        ]);
    }

    public function testExecuteWithImportantOption(): void
    {
        $parsedData = ['Core' => ['memory_limit' => []]];
        $filteredData = ['Core' => ['memory_limit' => []]];

        /** @var PhpInfoFilter&MockObject $filter */
        $filter = $this->createMock(PhpInfoFilter::class);

        $command = new InspectPhpInfoCommand(
            $this->fetcher,
            $this->parser,
            $filter,
            $this->renderer
        );
        $this->commandTester = new CommandTester($command);

        $this->fetcher->method('createTempFile')->willReturn('/tmp/file.php');
        $this->fetcher->method('fetchPhpInfo')->willReturn('html');
        $this->parser->method('parse')->willReturn($parsedData);

        $filter->expects($this->once())
            ->method('filterImportant')
            ->with($parsedData)
            ->willReturn($filteredData);

        $this->commandTester->execute([
            'base-url' => 'http://localhost',
            '--important' => true,
        ]);
    }

    public function testExecuteWithNoVerifyOption(): void
    {
        /** @var PhpInfoFetcher&MockObject $fetcher */
        $fetcher = $this->createMock(PhpInfoFetcher::class);

        $command = new InspectPhpInfoCommand(
            $fetcher,
            $this->parser,
            $this->filter,
            $this->renderer
        );
        $this->commandTester = new CommandTester($command);

        $fetcher->method('createTempFile')->willReturn('/tmp/file.php');
        $this->parser->method('parse')->willReturn(['Core' => []]);

        $fetcher->expects($this->once())
            ->method('fetchPhpInfo')
            ->with($this->anything(), true) // true for no-verify
            ->willReturn('html');

        $this->commandTester->execute([
            'base-url' => 'http://localhost',
            '--no-verify' => true,
        ]);
    }

    public function testExecuteWithEmptyParsedData(): void
    {
        $this->fetcher->method('createTempFile')->willReturn('/tmp/file.php');
        $this->fetcher->method('fetchPhpInfo')->willReturn('html');
        $this->parser->method('parse')->willReturn([]);

        $exitCode = $this->commandTester->execute([
            'base-url' => 'http://localhost',
        ]);

        $this->assertEquals(1, $exitCode);
        $this->assertStringContainsString('Could not parse phpinfo() output', $this->commandTester->getDisplay());
    }

    public function testExecuteWithNoFilterMatches(): void
    {
        $this->fetcher->method('createTempFile')->willReturn('/tmp/file.php');
        $this->fetcher->method('fetchPhpInfo')->willReturn('html');
        $this->parser->method('parse')->willReturn(['Core' => ['a' => 'b']]);
        $this->filter->method('filterSearch')->willReturn([]);

        $exitCode = $this->commandTester->execute([
            'base-url' => 'http://localhost',
            '--search' => 'nonexistent',
        ]);

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('No settings found matching your filters', $this->commandTester->getDisplay());
    }
}
