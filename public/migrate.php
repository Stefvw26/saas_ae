<?php
// fichier : public/migrate.php — CRM Auto-École, v0.21
declare(strict_types=1);

/**
 * Installation / mise à jour : base, migrations, données initiales,
 * paramètres par défaut, seeds des référentiels (par tenant), entretien.
 *
 * Navigation : http://localhost/saas_ae/public/migrate.php (debug only)
 * Terminal   : php C:\wamp64\www\saas_ae\public\migrate.php
 */

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/app/bootstrap.php';

use App\Core\Config;
use App\Core\Database;
use App\Repositories\LoginAttemptRepository;

const SEED_TENANT         = 'Auto-École Démo';
const SEED_AGENCE         = 'Agence Démo';
const SEED_LOGIN          = 'admin';
const SEED_PASSWORD       = 'Admin!1234';
const SEED_AGENCE_COULEUR = '#2563eb';
const SEED_USER_COULEUR   = '#4338ca';

/* Paramètres par défaut (CDC §25 + directives utilisateur). */
const PARAMETRES_DEFAUT = [
    ['nb_eleves_par_liste',      '20', 'entier'],
    ['nb_dossiers_cartouche',    '5',  'entier'],
    ['verifier_anniversaires',   '1',  'booleen'],
    ['activer_phrase_aleatoire', '0',  'booleen'],
    ['activer_ia',               '0',  'booleen'],
    ['ia_interne',               '0',  'booleen'],
    ['endpoint_ia',              '',   'chaine'],
    ['mot_de_passe_longueur',    '12', 'entier'],
    ['mot_de_passe_majuscules',  '1',  'booleen'],
    ['mot_de_passe_chiffres',    '1',  'booleen'],
    ['mot_de_passe_speciaux',    '1',  'booleen'],
];

/* Valeurs initiales des référentiels (CDC §23, §21, §24 + directives). */
const SEED_PROVENANCES = [
    'Passant', 'Facebook', 'Site internet', 'Vroum Vroum', 'X', 'Réseau personnel',
    'Prospecton', 'Moteur de recherche', 'Presse', 'TV', 'Autres sites', 'GO', 'Non précisé',
];
const SEED_TYPES_PRESTATIONS = [
    'ADMINISTRATIF', 'DIVERS', 'THÉORIE', 'PRATIQUE', 'SEJOUR & VOYAGE', 'DIVERS',
];
const SEED_PHRASES_OUVERTURE = [
    'Passez à la vitesse supérieure.',
    'Pensez à mettre à jour votre calendrier.',
    'N\'oubliez pas de relancer les inscriptions prospects.',
];
/* [nom, icone] — l'icône SVG est livrée ; un PNG de même base la remplace. */
const SEED_PARTENAIRES_TYPES = [
    ['Auto-école', 'partenaire-auto-ecole.svg'],
    ['Hôtel',      'partenaire-hotel.svg'],
];

 $isCli = PHP_SAPI === 'cli';
 $debug = (bool)Config::get('app.debug', false);

if (!$isCli && !$debug) {
    http_response_code(403);
    exit('Installation refusée : activez le mode debug (config/app.php) pour utiliser cet outil.');
}

 $log = [];
 $note = function (string $message, string $type = 'ok') use (&$log, $isCli): void {
    $log[] = ['message' => $message, 'type' => $type];
    if ($isCli) {
        echo ($type === 'ok' ? ' [OK] ' : ' [!!] ') . $message . PHP_EOL;
    }
};

/** Découpe un fichier SQL en instructions (les commentaires -- sont ignorés). */
function splitSql(string $sql): array
{
    $statements = [];
    $current = '';
    foreach (explode("\n", $sql) as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || strpos($trimmed, '--') === 0) {
            continue;
        }
        $current .= $line . "\n";
        if (substr(rtrim($trimmed), -1) === ';') {
            $statements[] = rtrim(trim($current), ';');
            $current = '';
        }
    }
    if (trim($current) !== '') {
        $statements[] = trim($current);
    }
    return $statements;
}

/** Nombre de lignes d'un référentiel pour un tenant. */
function compterReferentiel(string $table, int $tenantId): int
{
    $row = Database::fetch('SELECT COUNT(*) AS n FROM ' . $table . ' WHERE tenant_id = :t', ['t' => $tenantId]);
    return (int)($row['n'] ?? 0);
}

 $error = null;

try {
    /* 1. Création de la base si nécessaire */
    $db = Config::get('database', []);
    $server = new PDO(
        sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $db['host'] ?? '127.0.0.1', $db['port'] ?? 3306),
        (string)($db['username'] ?? ''),
        (string)($db['password'] ?? ''),
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $server->exec(
        'CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '', (string)$db['database']) . '`
         CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
    );
    $server = null;
    $note('Base de données « ' . (string)$db['database'] . ' » disponible.');

    /* 2. Table de suivi des migrations */
    Database::execute(
        'CREATE TABLE IF NOT EXISTS migrations (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            fichier VARCHAR(190) NOT NULL,
            execute_le DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY idx_migrations_fichier (fichier)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    /* 3. Migrations en attente */
    $applied = [];
    foreach (Database::fetchAll('SELECT fichier FROM migrations') as $row) {
        $applied[] = (string)$row['fichier'];
    }
    $files = glob(BASE_PATH . '/database/migrations/*.sql') ?: [];
    sort($files);
    $executed = 0;
    foreach ($files as $file) {
        $name = basename($file);
        if (in_array($name, $applied, true)) {
            continue;
        }
        foreach (splitSql((string)file_get_contents($file)) as $statement) {
            Database::pdo()->exec($statement);
        }
        Database::execute('INSERT INTO migrations (fichier) VALUES (:f)', ['f' => $name]);
        $executed++;
        $note('Migration exécutée : ' . $name);
    }
    if ($executed === 0) {
        $note('Aucune migration en attente (base à jour).');
    }

    /* 4. Données initiales (uniquement si aucun utilisateur) */
    $row = Database::fetch('SELECT COUNT(*) AS total FROM utilisateurs');
    if ((int)($row['total'] ?? 0) === 0) {
        Database::execute('INSERT INTO tenants (nom) VALUES (:nom)', ['nom' => SEED_TENANT]);
        $tenantId = Database::lastInsertId();

        Database::execute(
            'INSERT INTO agences (tenant_id, agence_nom, agence_couleur) VALUES (:t, :n, :c)',
            ['t' => $tenantId, 'n' => SEED_AGENCE, 'c' => SEED_AGENCE_COULEUR]
        );
        $agenceId = Database::lastInsertId();

        Database::execute(
            'INSERT INTO utilisateurs
                (tenant_id, login, mot_de_passe, mail, telephone, nom, prenom, couleur, role, tous_droit, agence)
             VALUES
                (:tenant, :login, :hash, :mail, :tel, :nom, :prenom, :couleur, :role, 1, :agence)',
            [
                'tenant'  => $tenantId,
                'login'   => SEED_LOGIN,
                'hash'    => password_hash(SEED_PASSWORD, PASSWORD_DEFAULT),
                'mail'    => 'admin@demo.local',
                'tel'     => '0600000000',
                'nom'     => 'Administrateur',
                'prenom'  => 'Compte',
                'couleur' => SEED_USER_COULEUR,
                'role'    => 'administrateur',
                'agence'  => $agenceId,
            ]
        );
        $note('Données initiales créées : tenant, agence, compte « ' . SEED_LOGIN . ' ».');
    } else {
        $note('Données initiales ignorées (des utilisateurs existent déjà).');
    }

    /* 5. Paramètres par défaut (par tenant, clés manquantes uniquement) */
    $ajoutes = 0;
    foreach (Database::fetchAll('SELECT id FROM tenants') as $tenant) {
        foreach (PARAMETRES_DEFAUT as [$cle, $valeur, $type]) {
            $existe = Database::fetch(
                'SELECT id FROM parametres WHERE tenant_id = :t AND cle = :c',
                ['t' => (int)$tenant['id'], 'c' => $cle]
            );
            if ($existe === null) {
                Database::execute(
                    'INSERT INTO parametres (tenant_id, cle, valeur, type) VALUES (:t, :c, :v, :ty)',
                    ['t' => (int)$tenant['id'], 'c' => $cle, 'v' => $valeur, 'ty' => $type]
                );
                $ajoutes++;
            }
        }
    }
    $note($ajoutes > 0 ? 'Paramètres par défaut créés (' . $ajoutes . ').' : 'Paramètres déjà présents.');

    /* 6. Seeds des référentiels (par tenant, table vide pour ce tenant) */
    $seeds = 0;
    foreach (Database::fetchAll('SELECT id FROM tenants') as $tenant) {
        $tid = (int)$tenant['id'];

        if (compterReferentiel('provenances', $tid) === 0) {
            foreach (SEED_PROVENANCES as $valeur) {
                Database::execute(
                    'INSERT INTO provenances (tenant_id, provenance, actif) VALUES (:t, :v, 1)',
                    ['t' => $tid, 'v' => $valeur]
                );
                $seeds++;
            }
        }
        if (compterReferentiel('types_prestations', $tid) === 0) {
            foreach (SEED_TYPES_PRESTATIONS as $valeur) {
                Database::execute(
                    'INSERT INTO types_prestations (tenant_id, nom, actif) VALUES (:t, :v, 1)',
                    ['t' => $tid, 'v' => $valeur]
                );
                $seeds++;
            }
        }
        if (compterReferentiel('phrase_ouverture', $tid) === 0) {
            foreach (SEED_PHRASES_OUVERTURE as $valeur) {
                Database::execute(
                    'INSERT INTO phrase_ouverture (tenant_id, phrase, actif) VALUES (:t, :v, 1)',
                    ['t' => $tid, 'v' => $valeur]
                );
                $seeds++;
            }
        }
        if (compterReferentiel('partenaires_type', $tid) === 0) {
            foreach (SEED_PARTENAIRES_TYPES as [$nom, $icone]) {
                Database::execute(
                    'INSERT INTO partenaires_type (tenant_id, nom, icone, actif) VALUES (:t, :v, :i, 1)',
                    ['t' => $tid, 'v' => $nom, 'i' => $icone]
                );
                $seeds++;
            }
        }
    }
    $note($seeds > 0
        ? 'Seeds référentiels créés (' . $seeds . ') : provenances §23, types de prestations §21, phrases §24, types de partenaires (icônes incluses).'
        : 'Seeds référentiels déjà présents.');

    /* 7. Entretien */
    (new LoginAttemptRepository())->purgeOlderThanDays(30);
    $note('Nettoyage des tentatives de connexion anciennes effectué.');
} catch (Throwable $e) {
    $error = $e->getMessage();
    $note('Erreur : ' . $error, 'err');
}

/* Sortie */
if ($isCli) {
    exit($error === null ? 0 : 1);
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Installation — CRM Auto-École · v0.21</title>
    <link href="<?= asset('css/app.css') ?>" rel="stylesheet">
</head>
<body class="auth-body">
<main class="auth-shell" style="max-width:620px">
    <div class="auth-brand">
        <svg width="44" height="44" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
            <circle cx="12" cy="12" r="3.2" fill="currentColor"/>
            <path d="M12 2v6.5M12 15.5V22M2 12h6.5M15.5 12H22" stroke="currentColor" stroke-width="2"/>
        </svg>
        <h1>Installation — v0.21</h1>
        <p>Migrations, données initiales et seeds des référentiels</p>
    </div>
    <div class="auth-card">
        <ul class="install-list">
            <?php foreach ($log as $entry): ?>
                <li class="install-item">
                    <span class="badge <?= $entry['type'] === 'ok' ? 'badge-ok' : 'badge-danger' ?>">
                        <?= $entry['type'] === 'ok' ? 'OK' : 'ERREUR' ?>
                    </span>
                    <span><?= e($entry['message']) ?></span>
                </li>
            <?php endforeach; ?>
            <?php if ($log === []): ?>
                <li class="install-item"><span class="badge badge-muted">—</span><span>Aucune opération.</span></li>
            <?php endif; ?>
        </ul>

        <?php if ($error === null): ?>
            <div class="alert alert-warning" role="alert">
                <span>
                    <?php
                    $row = Database::fetch('SELECT COUNT(*) AS total FROM utilisateurs');
                    if ((int)($row['total'] ?? 0) <= 1):
                    ?>
                        Compte initial : <strong><?= e(SEED_LOGIN) ?> / <?= e(SEED_PASSWORD) ?></strong><br>
                        Modifiez ce mot de passe dès la première connexion.
                    <?php else: ?>
                        Des comptes existent déjà : aucun identifiant n'a été créé ni modifié.
                    <?php endif; ?>
                </span>
            </div>
            <a class="btn btn-primary btn-block" href="<?= url('/') ?>">Ouvrir l'application</a>
        <?php else: ?>
            <div class="alert alert-danger" role="alert">
                <span>Installation interrompue : <?= e($error) ?></span>
            </div>
        <?php endif; ?>
    </div>
</main>
</body>
</html>