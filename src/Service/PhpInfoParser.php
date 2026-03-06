<?php

namespace Algoritma\PhpInfo\Service;

class PhpInfoParser
{
    /**
     * Parse phpinfo HTML → [ 'Section' => [ 'key' => ['local' => ..., 'default' => ...] ] ].
     *
     * @return array<string, array<string, array{local: string|null, default: string|null}>>
     */
    public function parse(string $html): array
    {
        if ($html === '' || $html === '0') {
            return [];
        }

        // Suppress warnings from malformed HTML
        $doc = new \DOMDocument();
        @$doc->loadHTML($html);
        $xpath = new \DOMXPath($doc);

        $result  = [];
        $section = 'General';

        // phpinfo() uses <h2> for section titles and <tr> for rows
        $nodes = $xpath->query('//h2 | //tr');

        foreach ($nodes as $node) {
            if ($node->nodeName === 'h2') {
                $section = trim($node->textContent);
                continue;
            }

            // <tr> rows: either 2 cols (key / local) or 3 cols (key / local / default)
            $cells = $xpath->query('td', $node);

            if ($cells->length === 2) {
                $key   = $this->cleanText($cells->item(0)->textContent);
                $local = $this->cleanText($cells->item(1)->textContent);
                if ($key === '') {
                    continue;
                }
                $result[$section][$key] = ['local' => $local, 'default' => null];
            } elseif ($cells->length === 3) {
                $key     = $this->cleanText($cells->item(0)->textContent);
                $local   = $this->cleanText($cells->item(1)->textContent);
                $default = $this->cleanText($cells->item(2)->textContent);
                if ($key === '') {
                    continue;
                }
                $result[$section][$key] = ['local' => $local, 'default' => $default];
            }
        }

        // Also grab the PHP version from the top
        $versionNode = $xpath->query('//h1[contains(text(),"PHP Version")]')->item(0)
            ?? $xpath->query('//*[contains(@class,"h")]')->item(0);

        if ($versionNode) {
            return ['PHP Version' => ['PHP Version' => ['local' => trim($versionNode->textContent), 'default' => null]]] + $result;
        }

        return $result;
    }

    private function cleanText(string $text): string
    {
        return trim((string) preg_replace('/\s+/', ' ', $text));
    }
}
