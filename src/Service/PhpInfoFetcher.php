<?php

namespace Algoritma\PhpInfo\Service;

class PhpInfoFetcher
{
    public function createTempFile(string $publicDir): string
    {
        if (! is_dir($publicDir)) {
            throw new \RuntimeException("Public directory not found: {$publicDir}");
        }

        if (! is_writable($publicDir)) {
            throw new \RuntimeException("Public directory is not writable: {$publicDir}");
        }

        // Random name to avoid collisions and guessing
        $fileName = '_phpinfo_' . bin2hex(random_bytes(8)) . '.php';
        $filePath = rtrim($publicDir, '/') . '/' . $fileName;

        $content = '<?php phpinfo();';

        if (file_put_contents($filePath, $content) === false) {
            throw new \RuntimeException("Could not write temp file: {$filePath}");
        }

        // Restrict permissions (readable by web server only)
        chmod($filePath, 0o644);

        return $filePath;
    }

    public function fetchPhpInfo(string $url, bool $noVerify = false): string
    {
        if (! function_exists('curl_init')) {
            throw new \RuntimeException('cURL extension is not available in CLI PHP.');
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_USERAGENT      => 'php-info-command/1.0',
            CURLOPT_SSL_VERIFYPEER => ! $noVerify,
            CURLOPT_SSL_VERIFYHOST => $noVerify ? 0 : 2,
        ]);

        $html     = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error !== '' && $error !== '0') {
            throw new \RuntimeException("cURL error: {$error}");
        }

        if ($httpCode !== 200) {
            throw new \RuntimeException("HTTP {$httpCode} returned from {$url}");
        }

        if (stripos($html, '<?php') !== false) {
            throw new \RuntimeException('The URL returned the PHP source code instead of executing it. Make sure your web server is configured to execute PHP files.');
        }

        if (stripos($html, 'phpinfo()') === false && stripos($html, 'PHP Version') === false) {
            throw new \RuntimeException('The URL does not seem to return a phpinfo() page.');
        }

        return $html;
    }
}
