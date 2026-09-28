<?php
// fichier : app/Controllers/AccountController.php — CRM Auto-École
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Logger;
use App\Core\Request;
use App\Core\Session;
use App\Repositories\UserRepository;
use App\Services\PolitiqueMotDePasse;

/**
 * Mon compte : changement de mot de passe, conforme à la politique
 * définie dans les paramètres (impossible de la contourner).
 */
final class AccountController extends Controller
{
    private UserRepository $users;

    public function __construct()
    {
        $this->users = new UserRepository();
    }

    public function show(Request $request): void
    {
        $this->view('account/password', [
            'title'     => 'Mon compte',
            'user'      => $request->user(),
            'politique' => PolitiqueMotDePasse::regles(),
        ]);
    }

    public function updatePassword(Request $request): void
    {
        $userId  = $request->userId() ?? 0;
        $current = (string)$request->post('mot_de_passe_actuel', '');
        $new     = (string)$request->post('nouveau_mot_de_passe', '');
        $confirm = (string)$request->post('confirmation_mot_de_passe', '');

        $row = $this->users->fetchPassword($userId);

        if ($row === null || !password_verify($current, (string)$row['mot_de_passe'])) {
            $this->flashError('Le mot de passe actuel est incorrect.');
            $this->redirect('/mon-compte');
            return;
        }

        $erreur = PolitiqueMotDePasse::valider($new);
        if ($erreur !== null) {
            $this->flashError($erreur);
            $this->redirect('/mon-compte');
            return;
        }
        if ($new !== $confirm) {
            $this->flashError('La confirmation ne correspond pas au nouveau mot de passe.');
            $this->redirect('/mon-compte');
            return;
        }
        if ($new === $current) {
            $this->flashError('Le nouveau mot de passe doit être différent de l\'actuel.');
            $this->redirect('/mon-compte');
            return;
        }

        $this->users->updatePassword($userId, password_hash($new, PASSWORD_DEFAULT));

        Logger::info('Mot de passe modifié', ['utilisateur' => (string)Session::get('user_login', '')]);

        $this->flashSuccess('Votre mot de passe a été modifié.');
        $this->redirect('/mon-compte');
    }
}