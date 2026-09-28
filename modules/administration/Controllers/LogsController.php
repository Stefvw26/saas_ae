<?php
// fichier : modules/administration/Controllers/LogsController.php
declare(strict_types=1);

namespace Modules\Administration\Controllers;

use App\Controllers\Controller;
use App\Core\Request;
use App\Services\Gate;
use Modules\Administration\Repositories\LogsRepository;

final class LogsController extends Controller
{
    private const PAR_PAGE = 30;

    private LogsRepository $journaux;

    public function __construct()
    {
        $this->journaux = new LogsRepository();
    }

    public function index(Request $request): void
    {
        $this->authorize('journaux.consulter');

        $tid   = (int)Gate::user()['tenant_id'];
        $scope = Gate::scopeAgence();

        $filtres = [
            'q'      => trim((string)$request->get('q', '')),
            'action' => (string)$request->get('action', ''),
            'de'     => (string)$request->get('de', ''),
            'a'      => (string)$request->get('a', ''),
        ];
        foreach (['de', 'a'] as $champDate) {
            if (!preg_match('#^\d{4}-\d{2}-\d{2}$#', $filtres[$champDate])) {
                $filtres[$champDate] = '';
            }
        }

        $total = $this->journaux->compter($tid, $scope, $filtres);
        $pages = max(1, (int)ceil($total / self::PAR_PAGE));
        $page  = min(max(1, (int)$request->get('page', 1)), $pages);

        $this->view('@administration/logs/index', [
            'title'   => 'Journaux d\'activité',
            'filtres' => $filtres,
            'actions' => $this->journaux->actionsDistinctes($tid),
            'liste'   => $this->journaux->paginer($tid, $scope, $filtres, $page, self::PAR_PAGE),
            'total'   => $total,
            'page'    => $page,
            'pages'   => $pages,
            'baseUrl' => '/administration/journaux',
        ]);
    }
}