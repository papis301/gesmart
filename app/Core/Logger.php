<?php
declare(strict_types=1);

namespace App\Core;

use Throwable;

/** Écrit dans la table `logs` existante, au même format que l'ancienne application. */
final class Logger
{
    public static function log(string $desc, ?int $userId = null, ?array $data = null): void
    {
        try {
            $now = date('Y-m-d H:i:s');
            Database::run(
                'INSERT INTO `logs` (`user_id`, `ip`, `desc`, `data`, `created_at`, `updated_at`) VALUES (?, ?, ?, ?, ?, ?)',
                [
                    $userId,
                    Request::ip(),
                    $desc,
                    $data === null ? null : json_encode($data, JSON_UNESCAPED_UNICODE),
                    $now,
                    $now,
                ]
            );
        } catch (Throwable $e) {
            error_log('Logger : ' . $e->getMessage());
        }
    }
}
