<?php
// fichier : app/Services/PolitiqueMotDePasse.php — CRM Auto-École
declare(strict_types=1);

namespace App\Services;

/**
 * Politique des mots de passe — pilotée par les paramètres (CDC §25, directive
 * utilisateur). Source unique utilisée par la création/modification d'utilisateurs
 * ET par « Mon compte » : impossible de contourner la politique.
 */
final class PolitiqueMotDePasse
{
    /** @return array{longueur:int, majuscules:bool, chiffres:bool, speciaux:bool} */
    public static function regles(): array
    {
        return [
            'longueur'   => max(8, (int)param('mot_de_passe_longueur', 12)),
            'majuscules' => (bool)param('mot_de_passe_majuscules', true),
            'chiffres'   => (bool)param('mot_de_passe_chiffres', true),
            'speciaux'   => (bool)param('mot_de_passe_speciaux', true),
        ];
    }

    /** Retourne le message d'erreur, ou null si le mot de passe est conforme. */
    public static function valider(string $motDePasse): ?string
    {
        $r = self::regles();

        if (mb_strlen($motDePasse) < $r['longueur']) {
            return 'Le mot de passe doit contenir au moins ' . $r['longueur'] . ' caractères.';
        }
        if ($r['majuscules'] && preg_match('/[A-Z]/', $motDePasse) !== 1) {
            return 'Le mot de passe doit contenir au moins une majuscule.';
        }
        if ($r['chiffres'] && preg_match('/\d/', $motDePasse) !== 1) {
            return 'Le mot de passe doit contenir au moins un chiffre.';
        }
        if ($r['speciaux'] && preg_match('/[^A-Za-z0-9]/', $motDePasse) !== 1) {
            return 'Le mot de passe doit contenir au moins un caractère spécial.';
        }
        return null;
    }
}