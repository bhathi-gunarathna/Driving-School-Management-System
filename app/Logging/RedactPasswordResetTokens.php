<?php

namespace App\Logging;

use Monolog\Logger;
use Monolog\LogRecord;

class RedactPasswordResetTokens
{
    public function __invoke(Logger $logger): void
    {
        $logger->pushProcessor(static function (LogRecord $record): LogRecord {
            return $record->with(message: self::redact($record->message));
        });
    }

    public static function redact(string $message): string
    {
        return preg_replace(
            '~(/reset-password/)[^/?\\s&#"<>]+~i',
            '$1[REDACTED]',
            $message
        ) ?? $message;
    }
}
