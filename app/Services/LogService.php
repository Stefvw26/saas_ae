<?php
// fichier : app/Services/LogService.php — CRM Auto-École, Jalon 2
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use Throwable;

/**
 * Journalisation applicative en base (table « logs ») — historique exploitable
 * (prompt maître §29 : utilisateur, date, action, objet, id, agence, résultat, infos).
 * Les erreurs techniques restent journalisées en fichier via Core\Logger.
 */
final class LogService
{
    public static function enregistrer(
        string $action,
        ?string $objet = null,
        ?int $objetId = null,
        string $resultat = 'succes',
        array $infos = []
    ): void {
        try {
            $user = Gate::user();
            Database::execute(
                'INSERT INTO logs (tenant_id, utilisateur_id, login, action, objet, objet_id, agence_id, resultat, infos, cree_le)
                 VALUES (:tenant, :utilisateur, :login, :action, :objet, :objet_id, :agence, :resultat, :infos, :cree_le)',
                [
                    'tenant'      => $user['tenant_id'] ?? null,
                    'utilisateur' => $user['id'] ?? null,
                    'login'       => $user['login'] ?? null,
                    'action'      => $action,
                    'objet'       => $objet,
                    'objet_id'    => $objetId,
                    'agence'      => Gate::scopeAgence(),
                    'resultat'    => $resultat,
                    'infos'       => $infos === [] ? null : json_encode($infos, JSON_UNESCAPED_UNICODE),
                    'cree_le'     => date('Y-m-d H:i:s'),
                ]
            );
        } catch (Throwable $e) {
            Logger::error('Journalisation impossible : ' . $e->getMessage(), ['action' => $action]);
        }
    }
}