<?php
declare(strict_types=1);

namespace App\Core;

final class Logger
{
    private const LOG_FILE = __DIR__ . '/../../logs/app.log';

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
