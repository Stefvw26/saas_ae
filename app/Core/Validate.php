<?php
// fichier : app/Core/Validate.php — CRM Auto-École, Jalon 2
declare(strict_types=1);

namespace App\Core;

/**
 * Mini-validateur serveur transverse.
 * Règles : required, email, date, hex, login, int, decimal, minlen: n, max: n, in: a,b,c
 */
final class Validate
{
    /**
     * @param array<string, mixed>  $data
     * @param array<string, string> $regles
     * @return array{0: array<string, string>, 1: array<string, ?string>} [erreurs, valeurs nettoyées]
     */
    public static function check(array $data, array $regles): array
    {
        $erreurs = [];
        $nettoyees = [];

        foreach ($regles as $champ => $chaineRegles) {
            $brut   = $data[$champ] ?? null;
            $valeur = is_string($brut) ? trim($brut) : $brut;
            $absent = ($valeur === null || $valeur === '');

            foreach (explode('|', (string)$chaineRegles) as $regle) {
                [$nom, $param] = array_pad(explode(':', trim($regle), 2), 2, null);

                if ($nom === 'required') {
                    if ($absent) {
                        $erreurs[$champ] = 'Champ obligatoire.';
                    }
                    continue;
                }
                if ($absent) {
                    continue; /* champ facultatif vide : pas de contrôle */
                }

                $erreur = self::appliquer($nom, $param, $valeur);
                if ($erreur !== null) {
                    $erreurs[$champ] = $erreur;
                    break;
                }
            }

            $nettoyees[$champ] = $absent ? null : (string)$valeur;
        }

        return [$erreurs, $nettoyees];
    }

    /** @param mixed $valeur */
    private static function appliquer(string $nom, ?string $param, $valeur): ?string
    {
        switch ($nom) {
            case 'email':
                return filter_var((string)$valeur, FILTER_VALIDATE_EMAIL) === false ? 'Adresse email invalide.' : null;
            case 'date':
                if (!preg_match('#^(\d{4})-(\d{2})-(\d{2})$#', (string)$valeur, $m)) {
                    return 'Date invalide (format AAAA-MM-JJ).';
                }
                return checkdate((int)$m[2], (int)$m[3], (int)$m[1]) ? null : 'Date invalide.';
            case 'hex':
    return preg_match('/^[ \t\n\r\f\v]*#[0-9a-fA-F]{6}[ \t\n\r\f\v]*$/u', (string)$valeur)? null : 'Couleur invalide (format #RRGGBB).';
         case 'login':
                return preg_match('#^[a-zA-Z0-9._-]{3,80}$#', (string)$valeur)
                    ? null
                    : 'Identifiant invalide (3 à 80 caractères : lettres, chiffres, . _ -).';
            case 'int':
                return ctype_digit((string)$valeur) ? null : 'Nombre entier attendu.';
            case 'decimal':
                return is_numeric($valeur) ? null : 'Nombre (décimal) attendu.';
            case 'minlen':
                return mb_strlen((string)$valeur) >= (int)$param ? null : 'Minimum ' . (int)$param . ' caractères.';
            case 'max':
                return mb_strlen((string)$valeur) <= (int)$param ? null : 'Maximum ' . (int)$param . ' caractères.';
            case 'in':
                return in_array((string)$valeur, explode(',', (string)$param), true) ? null : 'Valeur non autorisée.';
        }
        return null;
    }
}