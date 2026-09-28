<?php
// fichier : app/Controllers/NotificationsController.php — CRM Auto-École, J4
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Repositories\NotificationsRepository;
use App\Services\Gate;
use App\Services\LogService;

/**
 * Notifications personnelles : liste, lecture (marquage + redirection),
 * « tout marquer lu ». Chaque action est filtrée serveur sur
 * utilisateur_id + tenant_id (jamais les notifications d'autrui).
 */
final class NotificationsController extends Controller
{
    private NotificationsRepository $notifications;

    public function __construct()
    {
        $this->notifications = new NotificationsRepository();
    }

    /** @return array{0:int,1:int} [utilisateurId, tenantId] */
    private function identite(): array
    {
        $user = Gate::user();
        return [(int)($user['id'] ?? 0), (int)($user['tenant_id'] ?? 0)];
    }

    public function index(Request $request): void
    {
        [$uid, $tid] = $this->identite();

        $this->view('notifications/index', [
            'title'    => 'Notifications',
            'liste'    => $this->notifications->pourUtilisateur($uid, $tid),
            'nbNonLus' => $this->notifications->compterNonLus($uid, $tid),
        ]);
    }

    public function markAllRead(Request $request): void
    {
        [$uid, $tid] = $this->identite();

        $this->notifications->marquerToutLu($uid, $tid);
        LogService::enregistrer('notifications.tout_lu', 'notification', null, 'succes', []);

        $this->flashSuccess('Toutes les notifications sont marquées comme lues.');
        $this->redirect('/notifications');
    }

    public function read(Request $request, string $id): void
    {
        [$uid, $tid] = $this->identite();

        $notification = $this->notifications->trouverPourUtilisateur((int)$id, $uid, $tid);
        if ($notification === null) {
            abort(404, 'Notification introuvable', 'Cette notification n\'existe pas ou ne vous appartient pas.', '/notifications');
        }

        $this->notifications->marquerLu((int)$notification['id'], $uid, $tid);

        $cible = (string)($notification['url'] ?? '');
        if ($cible !== '' && $cible[0] === '/') {
            $this->redirect($cible);
            return;
        }
        $this->redirect('/notifications');
    }
}