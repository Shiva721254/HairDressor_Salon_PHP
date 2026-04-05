<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Environment Configuration Manager
 * 
 * Loads and manages environment variables from .env files.
 * Provides safe access to configuration with default fallbacks.
 */
final class Env
{
    /**
     * Load environment variables from a .env file
     * 
     * Parses KEY=VALUE format files and populates $_ENV and putenv().
     * Skips empty lines and comments (lines starting with #).
     * Does not override existing environment variables.
     * 
     * @param string $path Path to .env file
     */
    public static function load(string $path): void
    {
        if (!is_file($path)) return;

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) return;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) continue;

            [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
            $key = trim($key);
            $value = trim($value);

            if ($key === '') continue;

            if (getenv($key) === false) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
            }
        }
    }

    /**
     * Get an environment variable value with optional default
     * 
     * @param string $key The environment variable name
     * @param string $default Fallback value if not set
     * @return string The environment variable value or default
     */
    public static function get(string $key, string $default = ''): string
    {
        $val = getenv($key);
        if ($val === false || $val === '') return $default;
        return (string)$val;
    }
}
