<?php

namespace Algoritma\PhpInfo\Command;

use Algoritma\PhpInfo\Service\PhpInfoFetcher;
use Algoritma\PhpInfo\Service\PhpInfoFilter;
use Algoritma\PhpInfo\Service\PhpInfoParser;
use Algoritma\PhpInfo\Service\PhpInfoRenderer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'php:fpm-info',
    description: 'Fetches phpinfo() via HTTP and displays settings in a readable format',
)]
class InspectPhpInfoCommand extends Command
{
    /**
     * @var array<int, string>
     */
    private const array IMPORTANT_KEYS = [
        'memory_limit',
        'max_execution_time',
        'upload_max_filesize',
        'post_max_size',
        'max_input_vars',
        'display_errors',
        'error_reporting',
        'opcache.enable',
        'opcache.memory_consumption',
        'session.gc_maxlifetime',
        'date.timezone',
        'expose_php',
    ];

    public function __construct(
        private readonly PhpInfoFetcher $fetcher = new PhpInfoFetcher(),
        private readonly PhpInfoParser $parser = new PhpInfoParser(),
        /**
         * @param string[] $importantKeys
         */
        private readonly PhpInfoFilter $filter = new PhpInfoFilter(self::IMPORTANT_KEYS),
        /**
         * @param string[] $importantKeys
         */
        private readonly PhpInfoRenderer $renderer = new PhpInfoRenderer(self::IMPORTANT_KEYS),
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                'base-url',
                InputArgument::OPTIONAL,
                'Base URL of the app (e.g. https://myapp.local). Can also be set via APP_URL environment variable.'
            )
            ->addArgument(
                'public-dir',
                InputArgument::OPTIONAL,
                'Absolute path to the public directory (default: auto-detected). Can also be set via APP_PUBLIC_DIR environment variable.'
            )
            ->addOption(
                'section',
                's',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Filter by section name (e.g. --section=core --section=opcache)',
                []
            )
            ->addOption(
                'search',
                null,
                InputOption::VALUE_OPTIONAL,
                'Search for a specific key (e.g. --search=memory)'
            )
            ->addOption(
                'important',
                'i',
                InputOption::VALUE_NONE,
                'Show only the most important settings'
            )
            ->addOption(
                'no-verify',
                null,
                InputOption::VALUE_NONE,
                'Disable SSL certificate verification'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Register custom styles
        $output->getFormatter()->setStyle('key', new OutputFormatterStyle('cyan'));
        $output->getFormatter()->setStyle('val', new OutputFormatterStyle('white'));
        $output->getFormatter()->setStyle('local', new OutputFormatterStyle('green'));
        $output->getFormatter()->setStyle('default', new OutputFormatterStyle('gray'));
        $output->getFormatter()->setStyle('warn', new OutputFormatterStyle('yellow', null, ['bold']));
        $output->getFormatter()->setStyle('section', new OutputFormatterStyle('black', 'cyan', ['bold']));

        $baseUrl = $input->getArgument('base-url') ?: $_ENV['APP_URL'] ?? getenv('APP_URL');

        if (! $baseUrl) {
            $io->error('The "base-url" argument is required or must be set via APP_URL environment variable.');

            return Command::FAILURE;
        }

        $baseUrl = rtrim((string) $baseUrl, '/');
        $publicDir = $input->getArgument('public-dir') ?: $_ENV['APP_PUBLIC_DIR'] ?? getenv('APP_PUBLIC_DIR') ?: $this->guessPublicDir();
        $sections  = $input->getOption('section');
        $search    = $input->getOption('search');
        $important = $input->getOption('important');
        $noVerify  = $input->getOption('no-verify');

        try {
            $tmpFile = $this->fetcher->createTempFile($publicDir);
        } catch (\RuntimeException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $fileName = basename($tmpFile);
        $url      = "{$baseUrl}/{$fileName}";

        $io->title('PHP-FPM Info');
        $io->text("Temp file : <comment>{$tmpFile}</comment>");
        $io->text("Requesting: <href={$url}>{$url}</>");
        $io->newLine();

        try {
            $html = $this->fetcher->fetchPhpInfo($url, $noVerify);
        } catch (\RuntimeException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        } finally {
            if (file_exists($tmpFile)) {
                unlink($tmpFile);
                $io->text('<default>Temp file deleted.</default>');
                $io->newLine();
            }
        }

        $data = $this->parser->parse($html);

        if ($data === []) {
            $io->error('Could not parse phpinfo() output. Make sure the URL returns a valid phpinfo() page.');

            return Command::FAILURE;
        }

        if ($important) {
            $data = $this->filter->filterImportant($data);
        } elseif (! empty($sections)) {
            $data = $this->filter->filterSections($data, $sections);
        } elseif ($search !== null) {
            $data = $this->filter->filterSearch($data, $search);
        }

        if ($data === []) {
            $io->warning('No settings found matching your filters.');

            return Command::SUCCESS;
        }

        $this->renderer->render($output, $io, $data, $search);

        $io->newLine();
        $io->text('<default>Legend: <local>■</local> local/active value  <default>■</default> default value</>');

        return Command::SUCCESS;
    }

    private function guessPublicDir(): string
    {
        // Walk up from this file to find the Symfony project root
        $dir = __DIR__;
        for ($i = 0; $i < 5; ++$i) {
            $dir = dirname($dir);
            if (file_exists($dir . '/public/index.php')) {
                return $dir . '/public';
            }
        }

        // Fallback
        return dirname(__DIR__, 2) . '/public';
    }
}
