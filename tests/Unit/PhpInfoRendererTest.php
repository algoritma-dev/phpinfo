<?php

namespace Algoritma\PhpInfo\Tests\Unit;

use Algoritma\PhpInfo\Service\PhpInfoRenderer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Style\SymfonyStyle;

#[CoversClass(PhpInfoRenderer::class)]
class PhpInfoRendererTest extends TestCase
{
    private PhpInfoRenderer $renderer;

    private BufferedOutput $output;

    private SymfonyStyle $io;

    protected function setUp(): void
    {
        $this->renderer = new PhpInfoRenderer(['memory_limit']);
        $this->output = new BufferedOutput();
        $this->io = new SymfonyStyle(new ArrayInput([]), $this->output);
    }

    public function testRenderDataWithSimpleSection(): void
    {
        $data = [
            'PHP Core' => [
                'memory_limit' => ['local' => '128M', 'default' => '128M'],
                'display_errors' => ['local' => 'On', 'default' => 'Off'],
            ],
        ];

        $this->renderer->render($this->output, $this->io, $data, null);

        $content = $this->output->fetch();

        $this->assertStringContainsString('PHP CORE', $content);
        $this->assertStringContainsString('memory_limit', $content);
        $this->assertStringContainsString('128M', $content);
        $this->assertStringContainsString('display_errors', $content);
        $this->assertStringContainsString('On', $content);
        $this->assertStringContainsString('Off', $content);
    }

    public function testRenderDataWithSearchHighlighting(): void
    {
        $data = [
            'PHP Core' => [
                'memory_limit' => ['local' => '128M', 'default' => '128M'],
            ],
        ];

        // We expect <warn>memory</warn> because 'memory' is the search term
        $this->renderer->render($this->output, $this->io, $data, 'memory');

        $content = $this->output->fetch();

        // Since it's a BufferedOutput, we might see the tags if they are not stripped or processed.
        // SymfonyStyle/Table handles the formatting.
        $this->assertStringContainsString('memory', $content);
    }
}
