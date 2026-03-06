<?php

namespace Algoritma\PhpInfo\Service;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Terminal;

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
        $terminalWidth = $this->getTerminalWidth();
        // Allow some space for borders and the 'Setting' column
        // Setting column is usually around 20-40 chars.
        // We'll aim for a reasonable max width for values.
        $maxColumnWidth = max(20, (int) (($terminalWidth - 40) / 2));

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

                // Apply wrapping to long values
                $localFormatted   = $this->wrapText($localFormatted, $maxColumnWidth);
                $defaultFormatted = $this->wrapText($defaultFormatted, $maxColumnWidth);

                $tableRows[] = [$keyFormatted, $localFormatted, $defaultFormatted];
            }

            $table->setRows($tableRows);
            $table->render();
            $io->newLine();
        }
    }

    private function wrapText(string $text, int $width): string
    {
        // wordwrap doesn't work well with tags, but here the tags are at the beginning/end
        // of the string mostly. However, some values might be very long without spaces.

        // If it's a very long string without spaces (like some extension lists),
        // wordwrap with cut=true is necessary.

        // Simple regex to handle tags if they were inside, but here they are simple.
        // For now, let's use a simple approach.
        return wordwrap($text, $width, "\n", true);
    }

    private function getTerminalWidth(): int
    {
        $terminal = new Terminal();

        return $terminal->getWidth();
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
