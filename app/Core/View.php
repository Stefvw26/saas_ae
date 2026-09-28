<?php
// fichier : app/Core/View.php — CRM Auto-École, Jalon 2
declare(strict_types=1);

namespace App\Core;

use InvalidArgumentException;

/**
 * Rendu des vues PHP.
 * - template global   : relatif à /views ('dashboard/index')
 * - template de module: préfixé '@' ('@administration/utilisateurs/index')
 * Layouts globaux : 'app', 'auth', 'error', null = brut.
 */
final class View
{
    /** @param array<string, mixed> $data */
    public static function render(string $template, array $data = [], ?string $layout = 'app'): void
    {
        $file = self::resolve($template);
        if (!is_file($file)) {
            throw new InvalidArgumentException('Vue introuvable : ' . $template);
        }

        extract($data, EXTR_SKIP);
        unset($data, $template);

        ob_start();
        include $file;
        $content = (string)ob_get_clean();

        if ($layout === null) {
            echo $content;
            return;
        }

        $layoutFile = VIEW_PATH . '/layouts/' . $layout . '.php';
        if (!is_file($layoutFile)) {
            throw new InvalidArgumentException('Layout introuvable : ' . $layout);
        }
        include $layoutFile;
    }

    /** @param array<string, mixed> $data */
    public static function partial(string $template, array $data = []): void
    {
        $file = self::resolve($template);
        if (!is_file($file)) {
            throw new InvalidArgumentException('Composant introuvable : ' . $template);
        }
        extract($data, EXTR_SKIP);
        unset($data, $template);
        include $file;
    }

    private static function resolve(string $template): string
    {
        if ($template !== '' && $template[0] === '@') {
            $reste = substr($template, 1);
            $pos = strpos($reste, '/');
            if ($pos === false) {
                throw new InvalidArgumentException('Template de module invalide : ' . $template);
            }
            $module = substr($reste, 0, $pos);
            return MODULES_PATH . '/' . $module . '/views/' . substr($reste, $pos + 1) . '.php';
        }
        return VIEW_PATH . '/' . $template . '.php';
    }
}