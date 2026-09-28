<?php
// fichier : app/Services/NotificationService.php — CRM Auto-École, J4
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use Throwable;

/**
 * Service transverse de notification (J4 — prépare CDC §29).
 * Insertion jamais bloquante : toute erreur est journalisée et ignorée
 * (une notification ne doit jamais faire échouer l'action métier).
 *
 * Déclencheur actuel (décision documentée, à valider) : un nouveau
 * commentaire notifie les AUTRES participants de la conversation
 * (auteurs de commentaires précédents sur le même objet, hors l'auteur).
 */
final class NotificationService
{
    public static function notifier(int $utilisateurId, string $titre, string $message = '', ?string $url = null): void
    {
        try {
            $user = Gate::user();
            $tenantId = $user['tenant_id'] ?? null;
            if ($tenantId === null || $utilisateurId <= 0 || $utilisateurId === (int)($user['id'] ?? 0)) {
                return; /* jamais de notification pour soi-même */
            }
            Database::execute(
                'INSERT INTO notifications (tenant_id, utilisateur_id, titre, message, url, cree_le)
                 VALUES (:t, :u, :titre, :message, :url, :quand)',
                [
                    't'       => (int)$tenantId,
                    'u'       => $utilisateurId,
                    'titre'   => mb_substr($titre, 0, 150),
                    'message' => $message !== '' ? mb_substr($message, 0, 500) : null,
                    'url'     => $url,
                    'quand'   => date('Y-m-d H:i:s'),
                ]
            );
        } catch (Throwable $e) {
            Logger::error('Notification impossible : ' . $e->getMessage(), ['destinataire' => $utilisateurId]);
        }
    }

    /**
     * Notifie les participants d'une conversation de commentaires
     * (hors auteur du nouveau commentaire).
     */
    public static function notifierConversation(
        string $objetType,
        int $objetId,
        string $libelleType,
        string $libelleObjet,
        string $url,
        int $auteurId
    ): void {
        try {
            $user = Gate::user();
            $tenantId = (int)($user['tenant_id'] ?? 0);
            if ($tenantId === 0) {
                return;
            }

            $destinataires = Database::fetchAll(
                'SELECT DISTINCT utilisateur_id FROM commentaires
                 WHERE tenant_id = :t AND objet_type = :ty AND objet_id = :oi
                   AND utilisateur_id IS NOT NULL AND utilisateur_id != :ex',
                ['t' => $tenantId, 'ty' => $objetType, 'oi' => $objetId, 'ex' => $auteurId]
            );

            $message = 'Un commentaire a été ajouté sur ' . $libelleType
                . ($libelleObjet !== '' ? ' « ' . $libelleObjet . ' »' : '') . '.';

            foreach ($destinataires as $destinataire) {
                self::notifier((int)$destinataire['utilisateur_id'], 'Nouveau commentaire', $message, $url);
            }
        } catch (Throwable $e) {
            Logger::error('Notifications de conversation impossibles : ' . $e->getMessage(), ['objet' => $objetType]);
        }
    }
}