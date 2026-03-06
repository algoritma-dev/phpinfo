<?php

namespace Algoritma\PhpInfo\Tests\Unit;

use Algoritma\PhpInfo\Service\PhpInfoParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PhpInfoParser::class)]
class PhpInfoParserTest extends TestCase
{
    private PhpInfoParser $parser;

    protected function setUp(): void
    {
        $this->parser = new PhpInfoParser();
    }

    public function testParseEmptyHtmlReturnsEmptyArray(): void
    {
        $this->assertSame([], $this->parser->parse(''));
    }

    public function testParseSimpleSection(): void
    {
        $html = <<<'EOD'
            <html><body>
                        <h1 class="p">PHP Version 8.4.0</h1>
                        <h2>PHP Core</h2>
                        <table>
                            <tr class="h"><th>Directive</th><th>Local Value</th><th>Master Value</th></tr>
                            <tr><td class="e">memory_limit</td><td class="v">128M</td><td class="v">128M</td></tr>
                            <tr><td class="e">display_errors</td><td class="v">On</td><td class="v">Off</td></tr>
                        </table>
                    </body></html>
            EOD;

        $expected = [
            'PHP Version' => [
                'PHP Version' => ['local' => 'PHP Version 8.4.0', 'default' => null],
            ],
            'PHP Core' => [
                'memory_limit' => ['local' => '128M', 'default' => '128M'],
                'display_errors' => ['local' => 'On', 'default' => 'Off'],
            ],
        ];

        $this->assertEquals($expected, $this->parser->parse($html));
    }
}
