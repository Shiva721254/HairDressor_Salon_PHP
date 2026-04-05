<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Simple Application Logger
 * 
 * Writes timestamped log messages to a file.
 * Used for debugging and tracking application events.
 */
final class Logger
{
    /** @var string Path to log file */
    private const LOG_FILE = __DIR__ . '/../../logs/app.log';

    /**
     * Log an info-level message
     * 
     * @param string $message Message to log
     */
    public static function info(string $message): void
    {
        $line = sprintf(
            "[%s] INFO: %s%s",
            date('Y-m-d H:i:s'),
            $message,
            PHP_EOL
        );

        file_put_contents(self::LOG_FILE, $line, FILE_APPEND);
    }
}
