<?php
// fichier : app/Repositories/ReferentielRepository.php — CRM Auto-École, v0.21
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

/**
 * Socle générique des référentiels — composant transverse (§32).
 * Types de champs : text, textarea, color, entier, decimal, date,
 * booleen, select (options statiques + images), agence (FK + périmètre),
 * referer (FK externe), photo.
 *
 * v0.21 :
 * - where() protégé + hook colonnesSelect() (colonnes calculées : sous-requêtes) ;
 * - exporter() : toutes les lignes filtrées (export Excel §27) ;
 * - colonneIdentite : colonne image d'identification en tête de liste (véhicules) ;
 * - listeStatut / listeDate : masquer les colonnes génériques (listes épurées).
 */
abstract class ReferentielRepository
{
    protected string $table = '';
    protected string $colonneOrdre = 'nom';
    protected string $colonneDate = 'ajout_le';
    protected bool $suppressionLogique = true;
    protected bool $unicitePrincipale = true;
    protected bool $scoperAgence = false;
    protected int $parPage = 20;
    protected bool $listeStatut = true;
    protected bool $listeDate = true;

    /** @var array<string, array<string, mixed>> */
    protected array $champs = [];

    /** Colonne d'identification par image : ['champ' => …, 'images' => [valeur => fichier]]. */
    protected array $colonneIdentite = [];

    /** @return array<string, array<string, mixed>> */
    public function definition(): array { return $this->champs; }

    public function parPage(): int { return $this->parPage; }
    public function colonneDate(): string { return $this->colonneDate; }
    public function suppressionLogique(): bool { return $this->suppressionLogique; }
    public function unicitePrincipale(): bool { return $this->unicitePrincipale; }
    public function scoperAgence(): bool { return $this->scoperAgence; }
    public function listeStatut(): bool { return $this->listeStatut; }
    public function listeDate(): bool { return $this->listeDate; }

    /** @return array{champ:string, images:array<string, string>} */
    public function colonneIdentite(): array { return $this->colonneIdentite; }
        /** Conditions d'affichage par CARTE (v0.28) : groupe => ['champs' => [...]]. */
    protected array $conditionCartes = [];

    /** @return array<string, array{champs:array<int,string>}> */
    public function conditionCartes(): array { return $this->conditionCartes; }

    /** Premier champ = champ principal (libellé, unicité éventuelle). */
    public function champPrincipal(): ?string
    {
        $cles = array_keys($this->champs);
        return $cles[0] ?? null;
    }

    /** Colonnes du SELECT (surchargeable : sous-requêtes de comptage). */
    protected function colonnesSelect(): string
    {
        return '*';
    }

    /** Clause commune : tenant + suppression + périmètre agence + recherche texte. */
    protected function where(int $tenantId, ?int $scopeAgence, string $q, array &$params): string
    {
        $sql = ' WHERE tenant_id = :tenant' . ($this->suppressionLogique ? ' AND supprimer = 0' : '');
        $params['tenant'] = $tenantId;

        if ($this->scoperAgence && $scopeAgence !== null) {
            $sql .= ' AND agence = :scope';
            $params['scope'] = $scopeAgence;
        }

        $textuels = [];
        foreach ($this->champs as $nom => $def) {
            if (in_array($def['type'] ?? 'text', ['text', 'textarea'], true)) {
                $textuels[] = $nom;
            }
        }
        if ($q !== '' && $textuels !== []) {
            $conditions = [];
            $i = 0;
            foreach ($textuels as $nom) {
                $i++;
                $conditions[] = $nom . ' LIKE :q' . $i;
                $params['q' . $i] = '%' . $q . '%';
            }
            $sql .= ' AND (' . implode(' OR ', $conditions) . ')';
        }
        return $sql;
    }

    public function compter(int $tenantId, ?int $scopeAgence, string $q): int
    {
        $params = [];
        $row = Database::fetch(
            'SELECT COUNT(*) AS n FROM ' . $this->table . $this->where($tenantId, $scopeAgence, $q, $params),
            $params
        );
        return (int)($row['n'] ?? 0);
    }

    /** @return array<int, array<string, mixed>> */
    public function paginer(int $tenantId, ?int $scopeAgence, string $q, int $page): array
    {
        $params = ['limite' => $this->parPage, 'offset' => max(0, ($page - 1) * $this->parPage)];
        $sql = 'SELECT ' . $this->colonnesSelect() . ' FROM ' . $this->table
            . $this->where($tenantId, $scopeAgence, $q, $params)
            . ' ORDER BY ' . $this->colonneOrdre . ' LIMIT :limite OFFSET :offset';
        return Database::fetchAll($sql, $params);
    }

    /**
     * Toutes les lignes correspondant aux filtres (export Excel — CDC §27 :
     * l'export ne voit jamais plus que la liste : même tenant, même périmètre).
     *
     * @return array<int, array<string, mixed>>
     */
    public function exporter(int $tenantId, ?int $scopeAgence, string $q): array
    {
        $params = [];
        $sql = 'SELECT ' . $this->colonnesSelect() . ' FROM ' . $this->table
            . $this->where($tenantId, $scopeAgence, $q, $params)
            . ' ORDER BY ' . $this->colonneOrdre;
        return Database::fetchAll($sql, $params);
    }

    /** @return array<int, array<string, mixed>> */
    public function toutesPourTenant(int $tenantId, bool $uniquementActifs = true): array
    {
        $sql = 'SELECT * FROM ' . $this->table . ' WHERE tenant_id = :tenant'
            . ($this->suppressionLogique ? ' AND supprimer = 0' : '')
            . ($uniquementActifs ? ' AND actif = 1' : '')
            . ' ORDER BY ' . $this->colonneOrdre;
        return Database::fetchAll($sql, ['tenant' => $tenantId]);
    }

    /** @return array<string, mixed>|null */
    public function trouver(int $id, int $tenantId, ?int $scopeAgence = null): ?array
    {
        $params = ['id' => $id, 'tenant' => $tenantId];
        $sql = 'SELECT * FROM ' . $this->table . ' WHERE id = :id AND tenant_id = :tenant'
            . ($this->suppressionLogique ? ' AND supprimer = 0' : '');
        if ($this->scoperAgence && $scopeAgence !== null) {
            $sql .= ' AND agence = :scope';
            $params['scope'] = $scopeAgence;
        }
        $sql .= ' LIMIT 1';
        return Database::fetch($sql, $params);
    }

    public function valeurExiste(string $colonne, string $valeur, int $tenantId, ?int $horsId): bool
    {
        $params = ['valeur' => $valeur, 'tenant' => $tenantId];
        $sql = 'SELECT id FROM ' . $this->table . ' WHERE ' . $colonne . ' = :valeur AND tenant_id = :tenant'
            . ($this->suppressionLogique ? ' AND supprimer = 0' : '');
        if ($horsId !== null) {
            $sql .= ' AND id != :hors';
            $params['hors'] = $horsId;
        }
        $sql .= ' LIMIT 1';
        return Database::fetch($sql, $params) !== null;
    }

    /**
     * @param array<string, ?string|int> $valeurs champs éditables + 'actif'
     */
    public function creer(array $valeurs, int $tenantId, ?int $auteurId): int
    {
        $colonnes  = ['tenant_id'];
        $marqueurs = [':tenant'];
        $params    = ['tenant' => $tenantId];

        foreach ($this->champs as $nom => $def) {
            $colonnes[]  = $nom;
            $marqueurs[] = ':' . $nom;
            $params[$nom] = ($def['type'] ?? '') === 'booleen'
                ? (int)($valeurs[$nom] ?? 0)
                : ($valeurs[$nom] ?? null);
        }

        $colonnes[]  = 'actif';
        $marqueurs[] = ':actif';
        $params['actif'] = (int)($valeurs['actif'] ?? 1);

        $colonnes[]  = $this->colonneDate;
        $marqueurs[] = ':cree_le';
        $params['cree_le'] = date('Y-m-d H:i:s');

        $colonnes[]  = 'ajout_par';
        $marqueurs[] = ':auteur';
        $params['auteur'] = $auteurId;

        Database::execute(
            'INSERT INTO ' . $this->table . ' (' . implode(', ', $colonnes) . ') VALUES (' . implode(', ', $marqueurs) . ')',
            $params
        );
        return Database::lastInsertId();
    }

    /** @param array<string, ?string|int> $valeurs champs éditables + 'actif' */
    public function modifier(int $id, int $tenantId, array $valeurs): void
    {
        $set    = [];
        $params = ['id' => $id, 'tenant' => $tenantId];

        foreach ($this->champs as $nom => $def) {
            $set[] = $nom . ' = :' . $nom;
            $params[$nom] = ($def['type'] ?? '') === 'booleen'
                ? (int)($valeurs[$nom] ?? 0)
                : ($valeurs[$nom] ?? null);
        }
        $set[] = 'actif = :actif';
        $params['actif'] = (int)($valeurs['actif'] ?? 1);

        Database::execute(
            'UPDATE ' . $this->table . ' SET ' . implode(', ', $set) . ' WHERE id = :id AND tenant_id = :tenant',
            $params
        );
    }

    /** Suppression logique (archivage) ou définitive, selon la configuration. */
    public function supprimer(int $id, int $tenantId, ?int $auteurId): void
    {
        if ($this->suppressionLogique) {
            Database::execute(
                'UPDATE ' . $this->table . '
                 SET supprimer = 1, supprimer_par = :auteur, supprimer_date = :quand, actif = 0
                 WHERE id = :id AND tenant_id = :tenant',
                ['auteur' => $auteurId, 'quand' => date('Y-m-d H:i:s'), 'id' => $id, 'tenant' => $tenantId]
            );
            return;
        }
        Database::execute(
            'DELETE FROM ' . $this->table . ' WHERE id = :id AND tenant_id = :tenant',
            ['id' => $id, 'tenant' => $tenantId]
        );
    }
}