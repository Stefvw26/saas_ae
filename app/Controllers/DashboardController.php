<?php
// fichier : app/Controllers/DashboardController.php — CRM Auto-École, v0.25
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Config;
use App\Core\Database;
use App\Core\Request;
use App\Services\Gate;
use App\Services\SystemService;
use Throwable;

final class DashboardController extends Controller
{
    public function index(Request $request): void
    {
        $system = new SystemService();
        $user   = Gate::user();

        $this->view('dashboard/index', [
            'title'         => 'Tableau de bord',
            'user'          => $user,
            'phpVersion'    => PHP_VERSION,
            'appName'       => (string)Config::get('app.name', 'CRM Auto-École'),
            'appVersion'    => APP_VERSION,
            'anniversaires' => $this->anniversairesDuJour(),
            'system'        => [
                'dbOk'            => $system->databaseConnected(),
                'database'        => $system->databaseName(),
                'userCount'       => $system->userCount(),
                'migrationCount'  => $system->migrationCount(),
            ],
            'modules'       => $system->detectedModules(),
            'roadmap'       => $this->roadmap(),
        ]);
    }

       /**
     * Anniversaires du jour (CDC §26) — utilisateurs ET élèves (J6),
     * périmètre d'agence appliqué. Format : "Prénom Nom (élève)".
     *
     * @return array<int, string>
     */
    private function anniversairesDuJour(): array
    {
        $noms = [];
        try {
            if (!(bool)param('verifier_anniversaires', true)) {
                return $noms;
            }
            $user = Gate::user();
            if ($user === null) {
                return $noms;
            }
            $tid   = (int)$user['tenant_id'];
            $scope = Gate::scopeAgence();

            /* Utilisateurs / collaborateurs. */
            $params = ['t' => $tid, 'md' => date('m-d')];
            $sql = 'SELECT u.prenom, u.nom FROM utilisateurs u
                    WHERE u.tenant_id = :t AND u.supprimer = 0 AND u.actif = 1
                      AND u.date_de_naissance IS NOT NULL
                      AND DATE_FORMAT(u.date_de_naissance, "%m-%d") = :md';
            if ($scope !== null) {
                $sql .= ' AND u.agence = :s';
                $params['s'] = $scope;
            }
            $sql .= ' ORDER BY u.prenom, u.nom';
            foreach (Database::fetchAll($sql, $params) as $row) {
                $noms[] = trim((string)$row['prenom'] . ' ' . (string)$row['nom']);
            }

            /* Élèves (J6). */
            foreach ((new \Modules\Eleves\Repositories\ElevesRepository())
                     ->anniversairesDuJour($tid, $scope) as $nom) {
                $noms[] = $nom . ' (élève)';
            }
        } catch (Throwable) {
            /* jamais bloquant pour le tableau de bord */
        }
        return $noms;
    }

    /**
     * Feuille de route (prompt maître §14). J1-J3 livrés, J4 en cours.
     *
     * @return array<int, array{jalon:int, nom:string, etat:string}>
     */
    private function roadmap(): array
    {
        $livres = [
            [1, 'Socle MVC'],
            [2, 'Administration et fondations'],
            [3, 'Référentiels'],
        ];
        $aVenir = [
            [4,  'Services transverses'],
            [5,  'Prospects'],
            [6,  'Élèves'],
            [7,  'Dossiers et planning'],
            [8,  'Contrats et formules'],
            [9,  'Séjours'],
            [10, 'Communication'],
            [11, 'Sécurité avancée'],
            [12, 'IA'],
            [13, 'PWA'],
            [14, 'Offline et synchronisation'],
            [15, 'Stabilisation'],
        ];

        $roadmap = [];
        foreach ($livres as [$jalon, $nom]) {
            $roadmap[] = ['jalon' => $jalon, 'nom' => $nom, 'etat' => 'livre'];
        }
        foreach ($aVenir as [$jalon, $nom]) {
            $roadmap[] = ['jalon' => $jalon, 'nom' => $nom, 'etat' => 'avenir'];
        }
        return $roadmap;
    }
}