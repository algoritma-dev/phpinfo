<?php

namespace Algoritma\PhpInfo\Service;

class PhpInfoFilter
{
    /**
     * @param string[] $importantKeys
     */
    public function __construct(
        private readonly array $importantKeys = []
    ) {}

    /**
     * @param array<string, array<string, array{local: string|null, default: string|null}>> $data
     *
     * @return array<string, array<string, array{local: string|null, default: string|null}>>
     */
    public function filterImportant(array $data): array
    {
        $result = [];
        foreach ($data as $section => $rows) {
            foreach ($rows as $key => $values) {
                if (in_array(strtolower($key), $this->importantKeys, true)) {
                    $result[$section][$key] = $values;
                }
            }
        }

        return $result;
    }

    /**
     * @param array<string, array<string, array{local: string|null, default: string|null}>> $data
     * @param string[] $sections
     *
     * @return array<string, array<string, array{local: string|null, default: string|null}>>
     */
    public function filterSections(array $data, array $sections): array
    {
        $result = [];
        foreach ($sections as $filter) {
            foreach ($data as $section => $rows) {
                if (stripos($section, $filter) !== false) {
                    $result[$section] = $rows;
                }
            }
        }

        return $result;
    }

    /**
     * @param array<string, array<string, array{local: string|null, default: string|null}>> $data
     *
     * @return array<string, array<string, array{local: string|null, default: string|null}>>
     */
    public function filterSearch(array $data, string $search): array
    {
        $result = [];
        foreach ($data as $section => $rows) {
            foreach ($rows as $key => $values) {
                if (stripos($key, $search) !== false
                    || stripos($values['local'] ?? '', $search) !== false) {
                    $result[$section][$key] = $values;
                }
            }
        }

        return $result;
    }
}
