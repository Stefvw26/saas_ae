<?php
// fichier : app/Services/AuthService.php
declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Logger;
use App\Repositories\LoginAttemptRepository;
use App\Repositories\UserRepository;

/**
 * Authentification : vérification des identifiants + anti brute-force.
 */
final class AuthService
{
    /** Hash factice : temps de réponse comparable quand le login est inconnu. */
    private const DUMMY_HASH = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

    private UserRepository $users;
    private LoginAttemptRepository $attempts;
    private int $maxAttempts;
    private int $lockoutSeconds;

    public function __construct()
    {
        $this->users    = new UserRepository();
        $this->attempts = new LoginAttemptRepository();

        $security = Config::get('app.security', []);
        $minutes  = (int)($security['lockout_minutes'] ?? 15);
        $this->maxAttempts   = (int)($security['max_login_attempts'] ?? 5);
        $this->lockoutSeconds = max(60, $minutes * 60);
    }

    /**
     * @return array{success: bool, error: string|null, user: array|null}
     */
    public function attempt(string $login, string $password, string $ip): array
    {
        /* 1. Verrou anti brute-force */
        if ($this->attempts->countRecentFailures($login, $ip, $this->lockoutSeconds) >= $this->maxAttempts) {
            Logger::warning('Tentatives de connexion excessives', ['login' => $login, 'ip' => $ip]);
            return [
                'success' => false,
                'error'   => 'Trop de tentatives échouées. Merci de réessayer dans quelques minutes.',
                'user'    => null,
            ];
        }

        /* 2. Recherche et vérification */
        $user  = $this->users->findActiveByLogin($login);
        $hash  = $user['mot_de_passe'] ?? self::DUMMY_HASH;
        $valid = password_verify($password, $hash);

        if ($user === null || !$valid) {
            $this->attempts->record($login, $ip, false);
            Logger::info('Échec de connexion', ['login' => $login, 'ip' => $ip]);
            return ['success' => false, 'error' => 'Identifiants incorrects.', 'user' => null];
        }

        /* 3. Succès */
        $this->attempts->record($login, $ip, true);
        return ['success' => true, 'error' => null, 'user' => $user];
    }
}