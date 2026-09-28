<?php
// fichier : modules/prospects/Services/ParcoursService.php — v0.36
declare(strict_types=1);

namespace Modules\Prospects\Services;

use Modules\TypesPermis\Repositories\ParcoursRepository;

/**
 * Pont entre le GÉNÉRATEUR (questions créées par l'administrateur,
 * modules/types-permis) et le prospect : config du wizard, validation
 * serveur des réponses, libellés d'affichage.
 * Réponses stockées dans prospects.parcours en JSON {question_id: valeur}.
 */
final class ParcoursService
{
    /** @return array<int, array<string, mixed>> questions actives du type (avec options). */
    public static function questions(?int $typePermisId, int $tenantId): array
    {
        if ($typePermisId === null) {
            return [];
        }
        return (new ParcoursRepository())->questionsPourType($typePermisId, $tenantId, true);
    }

    /**
     * Validation serveur : chaque clé = question active du type ; valeur
     * conforme au type de réponse ; conditionnalités respectées.
     *
     * @param array<string, mixed> $brut
     * @return array<string, string>|null
     */
    public static function nettoyer(array $brut, ?int $typePermisId, int $tenantId): ?array
    {
        $questions = self::questions($typePermisId, $tenantId);
        if ($questions === []) {
            return null;
        }

        $parId = [];
        foreach ($questions as $q) {
            $parId[(int)$q['id']] = $q;
        }

        $reponses = [];
        foreach ($brut as $cle => $valeur) {
            $qid = (int)$cle;
            if (!isset($parId[$qid])) {
                continue; /* question inconnue / autre type : ignorée */
            }
            $q = $parId[$qid];
            $valeur = trim((string)$valeur);

            /* Condition non remplie : la réponse est ignorée. */
            if ($q['condition_question_id'] !== null) {
                $parent = (string)($brut[(string)$q['condition_question_id']] ?? ($brut[$q['condition_question_id']] ?? ''));
                if ($parent !== (string)$q['condition_valeur']) {
                    continue;
                }
            }

            if ($valeur === '') {
                continue;
            }
            switch ((string)$q['type_reponse']) {
                case 'oui_non':
                    if ($valeur === 'oui' || $valeur === 'non') {
                        $reponses[(string)$qid] = $valeur;
                    }
                    break;
                case 'choix':
                    if (isset($q['options'][$valeur])) {
                        $reponses[(string)$qid] = $valeur;
                    }
                    break;
                case 'nombre':
                    if (ctype_digit($valeur)) {
                        $reponses[(string)$qid] = (string)max(0, (int)$valeur);
                    }
                    break;
                case 'texte':
                    $reponses[(string)$qid] = mb_substr($valeur, 0, 500);
                    break;
            }
        }
        return $reponses === [] ? null : $reponses;
    }

    /**
     * Libellés d'affichage : [[libellé question, libellé réponse]].
     *
     * @param array<string, mixed> $reponses
     * @return array<int, array{0:string, 1:string}>
     */
    public static function libelles(?int $typePermisId, int $tenantId, array $reponses): array
    {
        $questions = self::questions($typePermisId, $tenantId);
        $parId = [];
        foreach ($questions as $q) {
            $parId[(int)$q['id']] = $q;
        }

        $lignes = [];
        foreach ($reponses as $cle => $valeur) {
            $qid = (int)$cle;
            if (!isset($parId[$qid])) {
                continue; /* question supprimée depuis : réponse orpheline ignorée */
            }
            $q = $parId[$qid];
            $valeur = (string)$valeur;

            if ($valeur === 'oui' || $valeur === 'non') {
                $libelleReponse = ucfirst($valeur);
            } elseif ((string)$q['type_reponse'] === 'choix') {
                $libelleReponse = $q['options'][$valeur] ?? $valeur;
            } elseif ((string)$q['type_reponse'] === 'nombre') {
                $libelleReponse = $valeur . ' h';
            } else {
                $libelleReponse = $valeur;
            }
            $lignes[] = [(string)$q['libelle'], $libelleReponse];
        }
        return $lignes;
    }

    /**
     * Config JS du wizard : questions (avec options et conditions) par id
     * de type de permis. Kind JS : bool/choix/nombre/texte.
     *
     * @param array<int, array<string, mixed>> $typesRows
     * @return array<string, mixed>
     */
    public static function configJs(array $typesRows, int $tenantId): array
    {
        $repo = new ParcoursRepository();
        $groupes = $repo->toutesPourTenant(
            $tenantId,
            array_map(static fn (array $tp): int => (int)$tp['id'], $typesRows)
        );

        $kinds = ['oui_non' => 'bool', 'choix' => 'choix', 'nombre' => 'nombre', 'texte' => 'texte'];
        $types = [];
        foreach ($typesRows as $tp) {
            $liste = [];
            foreach ($groupes[(int)$tp['id']] ?? [] as $q) {
                $liste[] = [
                    'id'      => (int)$q['id'],
                    'libelle' => (string)$q['libelle'],
                    'kind'    => $kinds[(string)$q['type_reponse']] ?? 'texte',
                    'options' => $q['options'],
                    'si'      => $q['condition_question_id'] !== null ? [
                        'cle'  => (int)$q['condition_question_id'],
                        'egal' => (string)$q['condition_valeur'],
                    ] : null,
                ];
            }
            $types[(string)$tp['id']] = $liste;
        }
        return ['types' => $types];
    }
}