<?php
// fichier : modules/prospects/Services/ParcoursDefinition.php
declare(strict_types=1);

namespace Modules\Prospects\Services;

/**
 * Définition des parcours prospects — CDC §41, règles VALIDÉES par
 * l'utilisateur (v0.35) :
 * - 'conduite' (ex. Permis B boîte auto / manuelle, Permis A2 moto) :
 *   inscription auto-école ; code de la route (+ ancienneté si oui) ;
 *   heures de conduite (+ nombre si oui) ;
 * - 'code' (ex. Code seul) : inscription auto-école ; code de la route
 *   (+ ancienneté si oui) ;
 * - 'aucun' (ex. Récupération de points, Annulation de permis) : pas de parcours.
 * Source unique : validation serveur, affichage fiche, config JS du wizard.
 */
final class ParcoursDefinition
{
    public const TYPE_CONDUITE = 'conduite';
    public const TYPE_CODE     = 'code';
    public const TYPE_AUCUN    = 'aucun';

    /** @return array<string, string> */
    public static function types(): array
    {
        return [
            self::TYPE_AUCUN    => 'Aucun parcours',
            self::TYPE_CONDUITE => 'Parcours permis (conduite)',
            self::TYPE_CODE     => 'Parcours code seul',
        ];
    }

    /**
     * Questions d'un type de parcours.
     *
     * @return array<int, array{cle:string, label:string, kind:string, options:array<string,string>, si:?array{cle:string, egal:string}}>
     */
    public static function questions(string $type): array
    {
        if ($type === self::TYPE_CONDUITE) {
            return [
                ['cle' => 'inscrit_auto_ecole', 'label' => 'Le prospect a déjà été inscrit dans une auto-école ?', 'kind' => 'bool', 'options' => [], 'si' => null],
                ['cle' => 'code_obtenu',        'label' => 'Le prospect a déjà obtenu son code de la route ?',      'kind' => 'bool', 'options' => [], 'si' => null],
                ['cle' => 'code_depuis',        'label' => 'Depuis combien de temps ?', 'kind' => 'choix',
                 'options' => [
                     'moins_4'   => 'Moins de 4 ans',
                     'entre_4_5' => 'Entre 4 et 5 ans',
                     'plus_5'    => 'Plus de 5 ans (code plus valide)',
                 ],
                 'si' => ['cle' => 'code_obtenu', 'egal' => 'oui']],
                ['cle' => 'heures_conduite', 'label' => 'Le prospect a déjà effectué des heures de conduite ?', 'kind' => 'bool', 'options' => [], 'si' => null],
                ['cle' => 'nb_heures',       'label' => 'Combien d\'heures de conduite ?', 'kind' => 'nombre', 'options' => [], 'si' => ['cle' => 'heures_conduite', 'egal' => 'oui']],
            ];
        }
        if ($type === self::TYPE_CODE) {
            return [
                ['cle' => 'inscrit_auto_ecole', 'label' => 'Le prospect a déjà été inscrit dans une auto-école ?', 'kind' => 'bool', 'options' => [], 'si' => null],
                ['cle' => 'code_obtenu',        'label' => 'Le prospect a déjà obtenu son code de la route ?',      'kind' => 'bool', 'options' => [], 'si' => null],
                ['cle' => 'code_depuis',        'label' => 'Depuis combien de temps ?', 'kind' => 'choix',
                 'options' => [
                     'moins_4'   => 'Moins de 4 ans',
                     'entre_4_5' => 'Entre 4 et 5 ans',
                     'plus_5'    => 'Plus de 5 ans (code plus valide)',
                 ],
                 'si' => ['cle' => 'code_obtenu', 'egal' => 'oui']],
            ];
        }
        return [];
    }

    /**
     * Nettoyage/validation serveur des réponses postées selon le type :
     * clés autorisées uniquement, conditionnalités respectées, valeurs conformes.
     *
     * @param array<string, mixed> $brut
     * @return array<string, string>|null null si vide / type sans parcours
     */
    public static function nettoyer(array $brut, string $type): ?array
    {
        if ($type === self::TYPE_AUCUN) {
            return null;
        }
        $reponses = [];
        foreach (self::questions($type) as $q) {
            $cle = $q['cle'];
            $valeur = isset($brut[$cle]) ? trim((string)$brut[$cle]) : '';

            /* Question conditionnelle : incluse seulement si le parent = oui. */
            if ($q['si'] !== null) {
                $parent = $reponses[$q['si']['cle']] ?? '';
                if ($parent !== $q['si']['egal']) {
                    continue;
                }
            }

            if ($q['kind'] === 'bool') {
                if ($valeur === 'oui' || $valeur === 'non') {
                    $reponses[$cle] = $valeur;
                }
            } elseif ($q['kind'] === 'choix') {
                if (isset($q['options'][$valeur])) {
                    $reponses[$cle] = $valeur;
                }
            } elseif ($q['kind'] === 'nombre') {
                if ($valeur !== '' && ctype_digit($valeur)) {
                    $reponses[$cle] = (string)max(0, (int)$valeur);
                }
            }
        }
        return $reponses === [] ? null : $reponses;
    }

    /** Libellé d'une question (tous types confondus). */
    public static function libelle(string $cle): string
    {
        foreach ([self::TYPE_CONDUITE, self::TYPE_CODE] as $type) {
            foreach (self::questions($type) as $q) {
                if ($q['cle'] === $cle) {
                    return $q['label'];
                }
            }
        }
        return $cle;
    }

    /** Libellé lisible d'une réponse. */
    public static function libelleReponse(string $cle, string $valeur): string
    {
        if ($valeur === 'oui' || $valeur === 'non') {
            return ucfirst($valeur);
        }
        foreach ([self::TYPE_CONDUITE, self::TYPE_CODE] as $type) {
            foreach (self::questions($type) as $q) {
                if ($q['cle'] === $cle) {
                    return $q['options'][$valeur] ?? $valeur;
                }
            }
        }
        if ($cle === 'nb_heures') {
            return $valeur . ' h';
        }
        return $valeur;
    }

    /**
     * Configuration JS du wizard : parcours_type par id de permis + questions.
     *
     * @param array<int, array<string, mixed>> $typesPermis
     * @return array<string, mixed>
     */
    public static function configJs(array $typesPermis): array
    {
        $map = [];
        foreach ($typesPermis as $tp) {
            $map[(string)$tp['id']] = (string)($tp['parcours_type'] ?? self::TYPE_AUCUN);
        }
        return [
            'types'     => $map,
            'questions' => [
                self::TYPE_CONDUITE => self::questions(self::TYPE_CONDUITE),
                self::TYPE_CODE     => self::questions(self::TYPE_CODE),
            ],
        ];
    }
}