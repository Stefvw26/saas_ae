<?php
// fichier : app/Core/Response.php
declare(strict_types=1);

namespace App\Core;

/**
 * Réponses HTTP simples.
 */
final class Response
{
    public static function redirect(string $url, int $status = 302): void
    {
        http_response_code($status);
        header('Location: ' . $url);
        exit;
    }

    /** @param array<string, mixed> $data */
    public static function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function status(int $code): void
    {
        http_response_code($code);
    }
}