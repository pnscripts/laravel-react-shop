<?php

namespace PnShop\Installer;

use RuntimeException;

/**
 * Edits the .env file: sets keys in place and keeps every other line and comment.
 * A missing .env starts as a copy of .env.example.
 */
final class EnvironmentFile
{
    public function __construct(private ?string $path = null) {}

    public function path(): string
    {
        return $this->path ?? app()->environmentFilePath();
    }

    /**
     * @param  array<string, string|int|bool|null>  $values
     */
    public function set(array $values): void
    {
        $path = $this->path();

        if (! is_file($path)) {
            $example = base_path('.env.example');
            $contents = is_file($example) ? (string) file_get_contents($example) : '';
        } else {
            $contents = (string) file_get_contents($path);
        }

        foreach ($values as $key => $value) {
            if (preg_match('/^[A-Z][A-Z0-9_]*$/', $key) !== 1) {
                throw new RuntimeException("Invalid environment key [{$key}].");
            }

            $line = $key.'='.$this->format($value);
            $pattern = '/^#?\s*'.preg_quote($key, '/').'=.*$/m';

            $contents = preg_match($pattern, $contents) === 1
                ? (string) preg_replace($pattern, str_replace(['\\', '$'], ['\\\\', '\\$'], $line), $contents, 1)
                : rtrim($contents)."\n".$line."\n";
        }

        if (file_put_contents($path, $contents) === false) {
            throw new RuntimeException("Could not write {$path}.");
        }
    }

    public function get(string $key): ?string
    {
        $path = $this->path();

        if (! is_file($path) || preg_match('/^'.preg_quote($key, '/').'=(.*)$/m', (string) file_get_contents($path), $match) !== 1) {
            return null;
        }

        return trim($match[1], " \t\"'");
    }

    private function format(string|int|bool|null $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        $value = (string) $value;

        if (preg_match('/[\r\n]/', $value) === 1) {
            throw new RuntimeException('Environment values cannot contain line breaks.');
        }

        // Quote anything beyond a plain token, escaping what dotenv interprets.
        return preg_match('/^[A-Za-z0-9_.\/:@+-]*$/', $value) === 1 ? $value : '"'.addcslashes($value, '"\\$').'"';
    }
}
