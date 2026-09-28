<?php
// fichier : app/Controllers/FichiersController.php — v0.37
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;

/**
 * Diffusion des fichiers téléversés (storage/uploads/{dossier}, hors zone
 * publique). Accès authentifié ; dossier en liste blanche ; nom validé.
 */
final class FichiersController extends Controller
{
    private const DOSSIERS = ['utilisateurs', 'centres', 'partenaires', 'types-permis'];

    private const MIMES = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'webp' => 'image/webp',
    ];

    public function stream(Request $request, string $dossier, string $fichier): void
    {
        if (!in_array($dossier, self::DOSSIERS, true)) {
            abort(404, 'Fichier introuvable', 'Le fichier demandé n\'existe pas.', '/');
        }
        if (!preg_match('#^[a-f0-9]{32}\.(jpg|jpeg|png|webp)$#', $fichier)) {
            abort(404, 'Fichier introuvable', 'Le fichier demandé n\'existe pas.', '/');
        }

        $chemin = STORAGE_PATH . '/uploads/' . $dossier . '/' . $fichier;
        if (!is_file($chemin)) {
            abort(404, 'Fichier introuvable', 'Le fichier demandé n\'existe pas.', '/');
        }

        $extension = strtolower(pathinfo($fichier, PATHINFO_EXTENSION));

        header('Content-Type: ' . self::MIMES[$extension]);
        header('Content-Length: ' . (string)filesize($chemin));
        header('Cache-Control: private, max-age=86400');
        readfile($chemin);
        exit;
    }
}