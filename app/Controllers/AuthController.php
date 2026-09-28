<?php
// fichier : app/Controllers/AuthController.php — CRM Auto-École, Jalon 2
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Logger;
use App\Core\Request;
use App\Core\Session;
use App\Services\AuthService;
use App\Services\LogService;

final class AuthController extends Controller
{
    private AuthService $auth;

    public function __construct()
    {
        $this->auth = new AuthService();
    }

    /** Formulaire de connexion. */
    public function showLogin(Request $request): void
    {
        $this->view('auth/login', ['title' => 'Connexion'], 'auth');
    }

    /** Traitement de la connexion. */
    public function login(Request $request): void
    {
        $login    = trim((string)$request->post('login', ''));
        $password = (string)$request->post('password', '');

        if ($login === '' || $password === '') {
            $this->flashError('Identifiant et mot de passe obligatoires.');
            Session::flash('old', ['login' => $login]);
            $this->redirect('/connexion');
            return;
        }

        $result = $this->auth->attempt($login, $password, $request->ip());

        if (!$result['success']) {
            $this->flashError((string)$result['error']);
            Session::flash('old', ['login' => $login]);
            $this->redirect('/connexion');
            return;
        }

        $user = $result['user'];

        /* Anti-fixation de session : nouvel identifiant à la connexion. */
        session_regenerate_id(true);

        Session::set('user_id', (int)$user['id']);
        Session::set('user_login', (string)$user['login']);
        Session::set('tenant_id', (int)$user['tenant_id']);
        Session::set('user_role', (string)$user['role']);
        Session::set('_created', time());
        Session::remove('agence_active_id'); /* l'agence active sera réinitialisée par le middleware */

        Logger::info('Connexion réussie', ['utilisateur' => $user['login'], 'ip' => $request->ip()]);
        LogService::enregistrer('utilisateur.connexion', 'utilisateur', (int)$user['id'], 'succes', ['ip' => $request->ip()]);

        $this->redirect('/');
    }

    /** Déconnexion (POST uniquement, protégée CSRF). */
    public function logout(Request $request): void
    {
        $userId = Session::has('user_id') ? (int)Session::get('user_id') : null;
        LogService::enregistrer('utilisateur.deconnexion', 'utilisateur', $userId, 'succes', ['ip' => $request->ip()]);
        Logger::info('Déconnexion', ['utilisateur' => (string)Session::get('user_login', ''), 'ip' => $request->ip()]);

        Session::destroy();
        $this->flashSuccess('Vous êtes déconnecté. À bientôt.');
        $this->redirect('/connexion');
    }
}