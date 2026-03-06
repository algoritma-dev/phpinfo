# PHP Info Inspector

A lightweight CLI tool to fetch and inspect PHP settings by running `phpinfo()` via HTTP in your web application's environment. This tool is particularly useful for verifying the actual configuration of a running FPM instance when CLI settings might differ.

## Features

- **Accurate Info**: Fetches settings from the perspective of the web server, not the CLI.
- **Secure**: Uses a randomly named temporary file that is automatically deleted after each request.
- **Filtering**: View only important settings, search for specific keys, or filter by extension/section.
- **Colorized Output**: Easy-to-read terminal output with a legend for local vs. default values.

## Requirements

- PHP 8.4 or higher.
- PHP cURL extension.
- Write access to the public directory of your web application.

## Installation

### Via Composer

You can install this tool as a dependency in your project:

```bash
composer require algoritma/phpinfo
```

Or install it globally:

```bash
composer global require algoritma/phpinfo
```

## Utilization

The tool provides a single command: `php:info`.

### Basic Usage

You must provide the base URL of your application. The tool will automatically attempt to guess your project's public directory.

```bash
bin/phpinfo https://myapp.local
```

### Configuration via Environment Variables

You can set default values in a `.env` file in your current directory:

```env
APP_URL=https://myapp.local
APP_PUBLIC_DIR=/var/www/html/public
```

### Advanced Options

- **Specific Public Directory**: If the tool cannot guess it correctly.
  ```bash
  bin/phpinfo https://myapp.local /var/www/html/my-public-dir
  ```

- **Filter by Section**: Show only specific extensions or sections.
  ```bash
  bin/phpinfo https://myapp.local --section=core --section=opcache
  ```

- **Search for a Key**: Find a specific configuration directive.
  ```bash
  bin/phpinfo https://myapp.local --search=memory
  ```

- **Show Important Settings**: Display a curated list of the most relevant production settings.
  ```bash
  bin/phpinfo https://myapp.local --important
  ```

- **Skip SSL Verification**: Useful for local development with self-signed certificates.
  ```bash
  bin/phpinfo https://myapp.local --no-verify
  ```

## Contributing

Contributions are welcome! To contribute, follow these steps:

1.  **Fork the repository**.
2.  **Install dependencies**:
    ```bash
    composer install
    ```
3.  **Run Quality Assurance**:
    Before submitting a PR, ensure all tests and quality checks pass:
    ```bash
    make qa
    ```
    This command runs:
    - **PHPUnit**: Unit and integration tests.
    - **PHPStan**: Static analysis.
    - **Rector**: Code upgrades and refactoring checks.
    - **PHP-CS-Fixer**: Coding style checks.

4.  **Fix Coding Style / Rector**:
    If there are issues, you can run:
    ```bash
    make qa-fix
    ```

### Project Structure

- `bin/`: The CLI entry point.
- `src/Command/`: Symfony Console command implementation.
- `src/Service/`: Logic for fetching, parsing, filtering, and rendering PHP info.
- `tests/`: PHPUnit tests.

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file (if present) or `composer.json` for details.
