<?php

namespace Algoritma\PhpInfo\Service;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class PhpInfoRenderer
{
    /**
     * @param string[] $importantKeys
     */
    public function __construct(
        private readonly array $importantKeys = []
    ) {}

    /**
     * @param array<string, array<string, array{local: string|null, default: string|null}>> $data
     */
    public function render(OutputInterface $output, SymfonyStyle $io, array $data, ?string $search): void
    {
        foreach ($data as $section => $rows) {
            if (empty($rows)) {
                continue;
            }

            // Section header
            $io->writeln(sprintf('<section> %s </section>', strtoupper($section)));
            $io->newLine();

            $table = new Table($output);
            $table->setStyle('box');
            $table->setHeaders([
                '<info>Setting</info>',
                '<info>Active Value</info>',
                '<info>Default Value</info>',
            ]);

            $tableRows = [];
            foreach ($rows as $key => $values) {
                $local   = $values['local'] ?? '';
                $default = $values['default'] ?? null;

                // Highlight important keys
                $isImportant = in_array(strtolower($key), $this->importantKeys, true);
                $keyFormatted = $isImportant
                    ? "<warn>{$key}</warn>"
                    : "<key>{$key}</key>";

                // Highlight search match
                if ($search !== null) {
                    $keyFormatted = str_ireplace(
                        $search,
                        "<warn>{$search}</warn>",
                        $keyFormatted
                    );
                }

                // Color local value
                $localFormatted = $this->formatValue($local);

                // Color default value (gray)
                $defaultFormatted = $default !== null && $default !== ''
                    ? "<default>{$default}</default>"
                    : '<default>—</default>';

                $tableRows[] = [$keyFormatted, $localFormatted, $defaultFormatted];
            }

            $table->setRows($tableRows);
            $table->render();
            $io->newLine();
        }
    }

    private function formatValue(string $value): string
    {
        // Color specific boolean-ish values
        return match (strtolower($value)) {
            'on', 'enabled', '1', 'yes', 'true'  => '<info>' . $value . '</info>',
            'off', 'disabled', '0', 'no', 'false' => '<comment>' . $value . '</comment>',
            'no value', ''                         => '<default>—</default>',
            default                                => '<local>' . $value . '</local>',
        };
    }
}
