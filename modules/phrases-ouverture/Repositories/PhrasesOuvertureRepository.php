<?php
// fichier : modules/phrases-ouverture/Repositories/PhrasesOuvertureRepository.php
declare(strict_types=1);

namespace Modules\PhrasesOuverture\Repositories;

use App\Core\Database;
use App\Repositories\ReferentielRepository;

/**
 * Référentiel des phrases d'ouverture — structure CDC §24
 * (table « phrase_ouverture », ajout_date, PAS de suppression logique :
 * suppression définitive) + phrase aléatoire pour l'écran de connexion.
 */
final class PhrasesOuvertureRepository extends ReferentielRepository
{
    protected string $table = 'phrase_ouverture';
    protected string $colonneOrdre = 'phrase';
    protected string $colonneDate = 'ajout_date';
    protected bool $suppressionLogique = false;
    protected bool $unicitePrincipale = false;

    /** @var array<string, array{label:string,type:string,requis:bool,max:int}> */
    protected array $champs = [
        'phrase' => ['label' => 'Phrase', 'type' => 'textarea', 'requis' => true, 'max' => 500],
    ];

    /**
     * Phrase active aléatoire pour l'écran de connexion (CDC §24), uniquement
     * si le paramètre « activer_phrase_aleatoire » est activé pour au moins un
     * tenant actif. Décision documentée : avant identification, le tenant
     * n'est pas résoluble — la phrase est tirée parmi les tenants concernés
     * (contenu d'accueil non sensible). À affiner lorsqu'une résolution de
     * tenant pré-connexion existera (sous-domaine, sélecteur).
     */
    public function phraseAleatoirePourConnexion(): ?string
    {
        $row = Database::fetch(
            'SELECT p.phrase
             FROM phrase_ouverture p
             INNER JOIN tenants t ON t.id = p.tenant_id AND t.actif = 1 AND t.supprimer = 0
             INNER JOIN parametres pa ON pa.tenant_id = p.tenant_id
                  AND pa.cle = :cle AND pa.valeur = :valeur
             WHERE p.actif = 1
             ORDER BY RAND()
             LIMIT 1',
            ['cle' => 'activer_phrase_aleatoire', 'valeur' => '1']
        );
        return $row !== null ? (string)$row['phrase'] : null;
    }
}