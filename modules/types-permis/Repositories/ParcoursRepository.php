<?php
// fichier : modules/types-permis/Repositories/ParcoursRepository.php — v0.41
declare(strict_types=1);

namespace Modules\TypesPermis\Repositories;

use App\Core\Database;

/**
 * Générateur de parcours (v0.41) :
 *  - questions/options par type de permis, auteur (ajout_par) ;
 *  - RÈGLES : definierCondition/effacerCondition (le conditionnement vit
 *    sur la question enfant en base, mais se RÈGLE depuis le parent) ;
 *  - modifierQuestion ne touche PLUS aux colonnes de condition ;
 *  - supprimerQuestionLogique détache les enfants conditionnés.
 */
final class ParcoursRepository
{
    private const SELECT_AVEC_AUTEUR = 'SELECT pq.*, u.login AS auteur_login, u.prenom AS auteur_prenom, u.nom AS auteur_nom
            FROM parcours_questions pq
            LEFT JOIN utilisateurs u ON u.id = pq.ajout_par';

    private function avecOptions(array $questions, int $tenantId): array
    {
        if ($questions === []) {
            return [];
        }
        foreach ($questions as &$q) {
            $rows = Database::fetchAll(
                'SELECT id, valeur, libelle FROM parcours_options WHERE question_id = :qid ORDER BY position, id',
                ['qid' => (int)$q['id']]
            );
            $map = [];
            $detail = [];
            foreach ($rows as $row) {
                $map[(string)$row['valeur']] = (string)$row['libelle'];
                $detail[] = ['id' => (int)$row['id'], 'valeur' => (string)$row['valeur'], 'libelle' => (string)$row['libelle']];
            }
            $q['options'] = $map;
            $q['options_detail'] = $detail;
        }
        return $questions;
    }

    /** @return array<int, array<string, mixed>> */
    public function questionsPourType(int $typePermisId, int $tenantId, bool $uniquementActives = true): array
    {
        $sql = self::SELECT_AVEC_AUTEUR . '
                WHERE pq.type_permis_id = :t AND pq.tenant_id = :tenant AND pq.supprimer = 0';
        if ($uniquementActives) {
            $sql .= ' AND pq.actif = 1';
        }
        $sql .= ' ORDER BY pq.position, pq.id';
        return $this->avecOptions(Database::fetchAll($sql, ['t' => $typePermisId, 'tenant' => $tenantId]), $tenantId);
    }

    /** @return array<int, array<int, array<string, mixed>>> */
    public function toutesPourTenant(int $tenantId, array $typesPermisIds = []): array
    {
        $sql = self::SELECT_AVEC_AUTEUR . '
                WHERE pq.tenant_id = :tenant AND pq.supprimer = 0';
        $params = ['tenant' => $tenantId];
        if ($typesPermisIds !== []) {
            $marqueurs = [];
            foreach (array_values($typesPermisIds) as $i => $id) {
                $marqueurs[] = ':tp' . $i;
                $params['tp' . $i] = (int)$id;
            }
            $sql .= ' AND pq.type_permis_id IN (' . implode(', ', $marqueurs) . ')';
        }
        $sql .= ' ORDER BY pq.type_permis_id, pq.position, pq.id';

        $groupe = [];
        foreach ($this->avecOptions(Database::fetchAll($sql, $params), $tenantId) as $q) {
            $groupe[(int)$q['type_permis_id']][] = $q;
        }
        return $groupe;
    }

    /** @return array<string, mixed>|null */
    public function trouverQuestion(int $id, int $tenantId): ?array
    {
        return Database::fetch(
            'SELECT * FROM parcours_questions WHERE id = :id AND tenant_id = :tenant AND supprimer = 0 LIMIT 1',
            ['id' => $id, 'tenant' => $tenantId]
        );
    }

    /**
     * @param array<string, ?string|int> $d
     * @param array<int, array{0:string, 1:string}> $options
     */
    public function creerQuestion(array $d, int $tenantId, int $auteurId, array $options = []): int
    {
        Database::execute(
            'INSERT INTO parcours_questions
                (tenant_id, type_permis_id, libelle, type_reponse, position, actif, ajout_le, ajout_par)
             VALUES
                (:tenant, :type, :libelle, :reponse, :position, :actif, :maintenant, :auteur)',
            [
                'tenant'     => $tenantId,
                'type'       => (int)$d['type_permis_id'],
                'libelle'    => $d['libelle'],
                'reponse'    => $d['type_reponse'],
                'position'   => (int)($d['position'] ?? 0),
                'actif'      => isset($_POST['actif']) ? 1 : 0,
                'maintenant' => date('Y-m-d H:i:s'),
                'auteur'     => $auteurId,
            ]
        );
        $qid = Database::lastInsertId();

        foreach ($options as $i => [$valeur, $libelle]) {
            if ($valeur === '' || $libelle === '') {
                continue;
            }
            $this->creerOption($qid, $valeur, $libelle, $i);
        }
        return $qid;
    }

    /** v0.41 : NE TOUCHE PLUS aux colonnes de condition (règles parent). */
    public function modifierQuestion(int $id, int $tenantId, array $d): void
    {
        Database::execute(
            'UPDATE parcours_questions SET
                libelle = :libelle, type_reponse = :reponse, position = :position, actif = :actif
             WHERE id = :id AND tenant_id = :tenant',
            [
                'libelle'  => $d['libelle'],
                'reponse'  => $d['type_reponse'],
                'position' => (int)($d['position'] ?? 0),
                'actif'    => isset($_POST['actif']) ? 1 : 0,
                'id'       => $id,
                'tenant'   => $tenantId,
            ]
        );
    }

    /** Suppression logique + détachement des enfants conditionnés sur elle. */
    public function supprimerQuestionLogique(int $id, int $tenantId, int $auteurId): void
    {
        Database::execute(
            'UPDATE parcours_questions SET supprimer = 1, supprimer_par = :auteur, supprimer_date = :quand, actif = 0
             WHERE id = :id AND tenant_id = :tenant',
            ['auteur' => $auteurId, 'quand' => date('Y-m-d H:i:s'), 'id' => $id, 'tenant' => $tenantId]
        );
        Database::execute(
            'UPDATE parcours_questions SET condition_question_id = NULL, condition_valeur = NULL
             WHERE condition_question_id = :id AND tenant_id = :tenant',
            ['id' => $id, 'tenant' => $tenantId]
        );
    }

    /* ---------- RÈGLES ---------- */

    /** « On affiche [enfant] si la réponse à [parent] est égale à [valeur] ». */
    public function definirCondition(int $enfantId, int $tenantId, int $parentId, string $valeur): void
    {
        Database::execute(
            'UPDATE parcours_questions SET condition_question_id = :parent, condition_valeur = :valeur
             WHERE id = :enfant AND tenant_id = :tenant',
            ['parent' => $parentId, 'valeur' => $valeur, 'enfant' => $enfantId, 'tenant' => $tenantId]
        );
    }

    public function effacerCondition(int $enfantId, int $tenantId): void
    {
        Database::execute(
            'UPDATE parcours_questions SET condition_question_id = NULL, condition_valeur = NULL
             WHERE id = :enfant AND tenant_id = :tenant',
            ['enfant' => $enfantId, 'tenant' => $tenantId]
        );
    }

    /* ---------- OPTIONS ---------- */

    /** @return array<string, mixed>|null */
    public function trouverOption(int $id, int $tenantId): ?array
    {
        return Database::fetch(
            'SELECT po.* FROM parcours_options po
             INNER JOIN parcours_questions pq ON pq.id = po.question_id AND pq.tenant_id = :tenant
             WHERE po.id = :id LIMIT 1',
            ['id' => $id, 'tenant' => $tenantId]
        );
    }

    public function creerOption(int $questionId, string $valeur, string $libelle, int $position = 0): void
    {
        Database::execute(
            'INSERT INTO parcours_options (question_id, valeur, libelle, position) VALUES (:q, :v, :l, :p)',
            ['q' => $questionId, 'v' => $valeur, 'l' => $libelle, 'p' => $position]
        );
    }

    public function supprimerOption(int $id): void
    {
        Database::execute('DELETE FROM parcours_options WHERE id = :id', ['id' => $id]);
    }

    public function valeurOptionExiste(int $questionId, string $valeur): bool
    {
        return Database::fetch(
            'SELECT id FROM parcours_options WHERE question_id = :q AND valeur = :v LIMIT 1',
            ['q' => $questionId, 'v' => $valeur]
        ) !== null;
    }

    /**
     * Valeurs possibles d'une question selon SON PROPRE type.
     * @return array<int, string>
     */
    public static function valeursPossibles(array $question): array
    {
        switch ((string)$question['type_reponse']) {
            case 'oui_non':
                return ['oui', 'non'];
            case 'choix':
                return array_values(array_map(
                    static fn (array $o): string => (string)$o['valeur'],
                    $question['options_detail'] ?? []
                ));
        }
        return [];
    }
}