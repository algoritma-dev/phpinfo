<?php

namespace Algoritma\PhpInfo\Tests\Unit;

use Algoritma\PhpInfo\Service\PhpInfoFilter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PhpInfoFilter::class)]
class PhpInfoFilterTest extends TestCase
{
    /**
     * @var array<string, array<string, array{local: string|null, default: string|null}>>
     */
    private array $data = [
        'PHP Core' => [
            'memory_limit' => ['local' => '128M', 'default' => '128M'],
            'display_errors' => ['local' => 'On', 'default' => 'Off'],
        ],
        'opcache' => [
            'opcache.enable' => ['local' => 'On', 'default' => 'On'],
        ],
    ];

    public function testFilterImportant(): void
    {
        $importantKeys = ['memory_limit', 'opcache.enable'];
        $filter = new PhpInfoFilter($importantKeys);

        $result = $filter->filterImportant($this->data);

        $this->assertArrayHasKey('PHP Core', $result);
        $this->assertArrayHasKey('memory_limit', $result['PHP Core']);
        $this->assertArrayNotHasKey('display_errors', $result['PHP Core']);
        $this->assertArrayHasKey('opcache', $result);
        $this->assertArrayHasKey('opcache.enable', $result['opcache']);
    }

    public function testFilterSections(): void
    {
        $filter = new PhpInfoFilter([]);

        $result = $filter->filterSections($this->data, ['opcache']);

        $this->assertArrayNotHasKey('PHP Core', $result);
        $this->assertArrayHasKey('opcache', $result);
    }

    public function testFilterSearch(): void
    {
        $filter = new PhpInfoFilter([]);

        $result = $filter->filterSearch($this->data, 'memory');

        $this->assertArrayHasKey('PHP Core', $result);
        $this->assertArrayHasKey('memory_limit', $result['PHP Core']);
        $this->assertArrayNotHasKey('display_errors', $result['PHP Core']);
    }
}
