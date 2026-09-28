<?php
// fichier : app/Helpers/functions.php — CRM Auto-École, v0.29
declare(strict_types=1);

use App\Core\Config;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Session;

/** Échappement HTML (protection XSS). */
function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** URL depuis la racine de l'application (gère le sous-répertoire WAMP). */
function url(string $path = ''): string
{
    static $base = null;
    if ($base === null) {
        $explicit = (string)Config::get('app.url', '');
        if ($explicit !== '') {
            $base = rtrim($explicit, '/');
        } else {
            $script = (string)($_SERVER['SCRIPT_NAME'] ?? '/index.php');
            $dir = rtrim(str_replace('\\', '/', dirname($script)), '/');
            $base = ($dir === '/' || $dir === '.') ? '' : $dir;
        }
    }
    return $base . '/' . ltrim($path, '/');
}

/** URL d'une ressource publique (versionnée pour casser le cache). */
function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/')) . '?v=' . APP_VERSION;
}

/**
 * URL d'une icône de public/assets/images/ avec priorité PNG :
 * un PNG de même base remplace automatiquement le SVG livré.
 */
function icone_url(string $fichier): string
{
    static $cache = [];
    if (isset($cache[$fichier])) {
        return $cache[$fichier];
    }

    $base = pathinfo($fichier, PATHINFO_FILENAME);
    if (strtolower(pathinfo($fichier, PATHINFO_EXTENSION) ?? '') !== 'png'
        && $base !== '' && is_file(BASE_PATH . '/public/assets/images/' . $base . '.png')) {
        return $cache[$fichier] = asset('images/' . $base . '.png');
    }
    return $cache[$fichier] = asset('images/' . $fichier);
}

/** Champ caché CSRF. */
function csrf_field(): string
{
    return Csrf::field();
}

/** Chemin courant (liens actifs de la sidebar). */
function current_path(): string
{
    static $path = null;
    if ($path === null) {
        $path = Request::currentPath();
    }
    return $path;
}

/* ---------- Droits (contrôle serveur, utilisable aussi dans les vues) ---------- */

function can(string $permission): bool
{
    return \App\Services\Gate::can($permission);
}

function tous_droit(): bool
{
    return \App\Services\Gate::tousDroit();
}

/* ---------- Multi-agence ---------- */

/** @return array<string, mixed>|null */
function agence_active(): ?array
{
    return \App\Services\Gate::agenceActive();
}

/** @return array<int, array<string, mixed>> */
function agences_accessibles(): array
{
    return \App\Services\Gate::agencesAccessibles();
}

function agence_scope(): ?int
{
    return \App\Services\Gate::scopeAgence();
}

/* ---------- Paramètres (administration, CDC §25) ---------- */

/** @return mixed */
function param(string $cle, $defaut = null)
{
    return \Modules\Administration\Services\ParametreService::get($cle, $defaut);
}

/* ---------- Téléphone (CDC §35 — composant transverse) ---------- */

/**
 * Référentiel des indicatifs : [iso => [nom, indicatif_sans_plus]].
 * Drapeaux servis par flagcdn.com (CDN, images 20px).
 *
 * @return array<string, array{0:string, 1:string}>
 */
function pays_indicatifs(): array
{
    return [
        'fr' => ['France', '33'],
        'be' => ['Belgique', '32'],
        'ch' => ['Suisse', '41'],
        'lu' => ['Luxembourg', '352'],
        'mc' => ['Monaco', '377'],
        'de' => ['Allemagne', '49'],
        'es' => ['Espagne', '34'],
        'it' => ['Italie', '39'],
        'pt' => ['Portugal', '351'],
        'gb' => ['Royaume-Uni', '44'],
        'ie' => ['Irlande', '353'],
        'nl' => ['Pays-Bas', '31'],
        'at' => ['Autriche', '43'],
        'gr' => ['Grèce', '30'],
        'pl' => ['Pologne', '48'],
        'ro' => ['Roumanie', '40'],
        'cz' => ['Tchéquie', '420'],
        'sk' => ['Slovaquie', '421'],
        'hu' => ['Hongrie', '36'],
        'bg' => ['Bulgarie', '359'],
        'hr' => ['Croatie', '385'],
        'rs' => ['Serbie', '381'],
        'ua' => ['Ukraine', '380'],
        'ru' => ['Russie', '7'],
        'se' => ['Suède', '46'],
        'no' => ['Norvège', '47'],
        'dk' => ['Danemark', '45'],
        'fi' => ['Finlande', '358'],
        'ma' => ['Maroc', '212'],
        'dz' => ['Algérie', '213'],
        'tn' => ['Tunisie', '216'],
        'sn' => ['Sénégal', '221'],
        'ci' => ['Côte d\'Ivoire', '225'],
        'cm' => ['Cameroun', '237'],
        'cg' => ['Congo', '242'],
        'cd' => ['RD Congo', '243'],
        'ga' => ['Gabon', '241'],
        'gn' => ['Guinée', '224'],
        'ml' => ['Mali', '223'],
        'ne' => ['Niger', '227'],
        'tg' => ['Togo', '228'],
        'bj' => ['Bénin', '229'],
        'bf' => ['Burkina Faso', '226'],
        'mu' => ['Maurice', '230'],
        'mg' => ['Madagascar', '261'],
        'ca' => ['Canada', '1'],
        'us' => ['États-Unis', '1'],
        'mx' => ['Mexique', '52'],
        'br' => ['Brésil', '55'],
        'ar' => ['Argentine', '54'],
        'co' => ['Colombie', '57'],
        'tr' => ['Turquie', '90'],
        'cn' => ['Chine', '86'],
        'jp' => ['Japon', '81'],
        'in' => ['Inde', '91'],
        'vn' => ['Vietnam', '84'],
        'au' => ['Australie', '61'],
    ];
}

/**
 * Sépare « +33 6 12 34 56 78 » en ['33', '6 12 34 56 78'].
 * Sans indicatif (+…) : ['', valeur intégrale].
 *
 * @return array{0:string, 1:string}
 */
function telephone_splitter(string $valeur): array
{
    $valeur = trim($valeur);
    if ($valeur !== '' && $valeur[0] === '+') {
        if (preg_match('#^\+(\d{1,4})(?:[\s.\-]*(.*))?$#', $valeur, $m) === 1) {
            return [$m[1], trim((string)($m[2] ?? ''))];
        }
    }
    return ['', $valeur];
}

/* ---------- Répétition de formulaires ---------- */

/** @return array<string, string> */
function old_values(): array
{
    static $anciennes = null;
    if ($anciennes === null) {
        $brut = Session::getFlash('old', []);
        $anciennes = is_array($brut) ? $brut : [];
    }
    return $anciennes;
}

function old(string $key, string $default = ''): string
{
    $anciennes = old_values();
    return isset($anciennes[$key]) && is_scalar($anciennes[$key]) ? (string)$anciennes[$key] : $default;
}

/** @return array<string, string> */
function form_errors(): array
{
    static $erreurs = null;
    if ($erreurs === null) {
        $brut = Session::getFlash('errors', []);
        $erreurs = is_array($brut) ? $brut : [];
    }
    return $erreurs;
}

function form_error(string $key): string
{
    $erreurs = form_errors();
    return isset($erreurs[$key]) ? (string)$erreurs[$key] : '';
}

/* ---------- Interruption avec page d'erreur ---------- */

function abort(int $code, string $titre, string $message = '', string $link = '/'): void
{
    http_response_code($code);
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }
    try {
        \App\Core\View::render('errors/generic', [
            'title'   => $code . ' — ' . $titre,
            'code'    => $code,
            'titre'   => $titre,
            'message' => $message,
            'link'    => $link,
        ], 'error');
    } catch (Throwable $ignore) {
        echo '<!doctype html><html lang="fr"><meta charset="utf-8"><title>' . (int)$code . '</title>'
           . '<body><h1>' . (int)$code . ' — ' . htmlspecialchars($titre, ENT_QUOTES) . '</h1></body></html>';
    }
    exit;
}

/* ---------- Divers ---------- */

function user_initials(array $user): string
{
    $first = mb_substr((string)($user['prenom'] ?? ''), 0, 1);
    $last  = mb_substr((string)($user['nom'] ?? ''), 0, 1);
    $initials = mb_strtoupper($first . $last);
    return $initials !== '' ? $initials : '?';
}

function role_label(string $role): string
{
    $roles = [
        'visiteur'       => 'Visiteur',
        'employe'        => 'Employé',
        'moniteur'       => 'Moniteur',
        'directeur'      => 'Directeur',
        'administrateur' => 'Administrateur',
    ];
    return $roles[$role] ?? ucfirst($role);
}
/**
 * Couleur pastel STABLE générée depuis une graine (ex. nom complet) :
 * avatar d'initiale garanti coloré même sans couleur d'entité (v0.36).
 */
function couleur_pastel(string $seed): string
{
    $h = (float)(abs(crc32($seed)) % 360);
    $s = 0.60;
    $l = 0.42;

    $c = (1 - abs(2 * $l - 1)) * $s;
    $x = $c * (1 - abs(fmod($h / 60.0, 2.0) - 1.0));
    $m = $l - $c / 2;

    $r = 0.0; $g = 0.0; $b = 0.0;
    if ($h < 60)       { $r = $c; $g = $x; }
    elseif ($h < 120)  { $r = $x; $g = $c; }
    elseif ($h < 180)  { $g = $c; $b = $x; }
    elseif ($h < 240)  { $g = $x; $b = $c; }
    elseif ($h < 300)  { $r = $x; $b = $c; }
    else               { $r = $c; $b = $x; }

    $to255 = static function (float $v): int {
        return (int)round(($v + $m) * 255);
    };
    return sprintf('#%02x%02x%02x', $to255($r), $to255($g), $to255($b));
}

/**
 * Téléphone « joli » (v0.36) : découpe + ISO du pays pour le drapeau.
 *
 * @return array{iso:string, indicatif:string, numero:string, complet:string}
 */
function telephone_pretty(string $valeur): array
{
    [$indicatif, $numero] = telephone_splitter($valeur);
    $iso = '';
    if ($indicatif !== '') {
        foreach (pays_indicatifs() as $code => $info) {
            if ($info[1] === $indicatif) {
                $iso = $code;
                break;
            }
        }
    }
    return [
        'iso'       => $iso,
        'indicatif' => $indicatif,
        'numero'    => $numero,
        'complet'   => trim(($indicatif !== '' ? '+' . $indicatif . ' ' : '') . $numero),
    ];
}