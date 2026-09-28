<?php
// fichier : app/Core/Handler.php — CRM Auto-École, Jalon 1
declare(strict_types=1);

namespace App\Core;

use ErrorException;
use Throwable;

/**
 * Gestion centralisée des erreurs et exceptions : journalisation
 * systématique + page 500 (détaillée uniquement en mode debug).
 */
final class Handler
{
    public static function exception(Throwable $e): void
    {
        Logger::error('Exception non interceptée : ' . $e->getMessage(), [
            'exception' => get_class($e),
            'file'      => $e->getFile(),
            'line'      => $e->getLine(),
        ]);

        while (ob_get_level() > 0) {
            @ob_end_clean();
        }
        http_response_code(500);

        try {
            if ((bool)Config::get('app.debug', false)) {
                View::render('errors/500_debug', [
                    'title'     => '500 — Erreur interne (debug)',
                    'exception' => $e,
                ], 'error');
            } else {
                View::render('errors/500', [
                    'title' => '500 — Erreur interne',
                ], 'error');
            }
        } catch (Throwable $ignored) {
            echo '<!doctype html><html lang="fr"><meta charset="utf-8"><title>500</title>'
               . '<body><h1>500 — Erreur interne</h1><p>Une erreur inattendue est survenue. L\'incident a été journalisé.</p></body></html>';
        }
        exit(1);
    }

    /** Convertit les erreurs PHP en exceptions (même canal de traitement). */
    public static function error(int $no, string $str, string $file, int $line): bool
    {
        if (!(error_reporting() & $no)) {
            return false; /* erreur silencée (@) : laisser PHP gérer */
        }
        throw new ErrorException($str, 0, $no, $file, $line);
    }

    public static function shutdown(): void
    {
        $err = error_get_last();
        if ($err === null) {
            return;
        }
        $fatal = [E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING];
        if (in_array($err['type'], $fatal, true)) {
            self::exception(new ErrorException($err['message'], 0, $err['type'], $err['file'], $err['line']));
        }
    }
}