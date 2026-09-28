<?php
// fichier : app/Services/RechercheService.php — CRM Auto-École, J4 (CDC §12/§28)
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * Recherche globale transversale — TOUJOURS disponible (CDC §12).
 * Respecte : permissions (.consulter par section), isolation tenant,
 * périmètre d'agence (utilisateurs, véhicules) — CDC §33 : la recherche
 * ne doit jamais devenir un moyen de contourner les restrictions.
 * Sections couvertes à ce jour : utilisateurs, agences, domaines,
 * véhicules, centres, partenaires, prestations, provenances, types de
 * permis, types de prestations, phrases d'ouverture. Élèves, prospects
 * et dossiers s'ajouteront à leurs jalons (J5-J7).
 */
final class RechercheService
{
    private const LIMITE = 6;

    /**
     * @return array<int, array<string, mixed>> sections avec résultats uniquement
     */
    public function resultats(string $q): array
    {
        $q = trim($q);
        if ($q === '') {
            return [];
        }
        $user = Gate::user();
        if ($user === null) {
            return [];
        }

        $tid   = (int)$user['tenant_id'];
        $scope = Gate::scopeAgence();
        $like  = '%' . $q . '%';

        $defs = [
            ['label' => 'Utilisateurs', 'perm' => 'utilisateurs.consulter', 'permMod' => 'utilisateurs.modifier', 'table' => 'utilisateurs', 'alias' => 'u', 'agence' => true, 'colonnes' => ['nom', 'prenom', 'login', 'mail', 'telephone'], 'titre' => "CONCAT_WS(' ', u.prenom, u.nom)", 'sous' => 'u.login', 'routeEdit' => '/administration/utilisateurs/', 'routeListe' => '/administration/utilisateurs'],
            ['label' => 'Agences', 'perm' => 'agences.consulter', 'permMod' => 'agences.modifier', 'table' => 'agences', 'alias' => 'a', 'agence' => false, 'colonnes' => ['agence_nom', 'agence_adresse'], 'titre' => 'a.agence_nom', 'sous' => 'a.agence_adresse', 'routeEdit' => '/administration/agences/', 'routeListe' => '/administration/agences'],
            ['label' => 'Domaines', 'perm' => 'domaines.consulter', 'permMod' => 'domaines.modifier', 'table' => 'domaines', 'alias' => 'd', 'agence' => false, 'colonnes' => ['nom', 'descriptif'], 'titre' => 'd.nom', 'sous' => 'd.descriptif', 'routeEdit' => '/domaines/', 'routeListe' => '/domaines'],
            ['label' => 'Véhicules', 'perm' => 'vehicules.consulter', 'permMod' => 'vehicules.modifier', 'table' => 'vehicules', 'alias' => 'v', 'agence' => true, 'colonnes' => ['immatriculation', 'marque', 'model'], 'titre' => 'v.immatriculation', 'sous' => "CONCAT_WS(' ', v.marque, v.model)", 'routeEdit' => '/vehicules/', 'routeListe' => '/vehicules'],
            ['label' => 'Centres', 'perm' => 'centres.consulter', 'permMod' => 'centres.modifier', 'table' => 'centres', 'alias' => 'c', 'agence' => false, 'colonnes' => ['nom', 'ville'], 'titre' => 'c.nom', 'sous' => 'c.ville', 'routeEdit' => '/centres/', 'routeListe' => '/centres'],
            ['label' => 'Partenaires', 'perm' => 'partenaires.consulter', 'permMod' => 'partenaires.modifier', 'table' => 'partenaires', 'alias' => 'pa', 'agence' => false, 'colonnes' => ['nom', 'ville'], 'titre' => 'pa.nom', 'sous' => 'pa.ville', 'routeEdit' => '/partenaires/', 'routeListe' => '/partenaires'],
            ['label' => 'Prestations', 'perm' => 'prestations.consulter', 'permMod' => 'prestations.modifier', 'table' => 'prestations', 'alias' => 'pr', 'agence' => false, 'colonnes' => ['titre', 'sku'], 'titre' => 'pr.titre', 'sous' => 'pr.sku', 'routeEdit' => '/prestations/', 'routeListe' => '/prestations'],
            ['label' => 'Provenances', 'perm' => 'provenances.consulter', 'permMod' => 'provenances.modifier', 'table' => 'provenances', 'alias' => 'pv', 'agence' => false, 'colonnes' => ['provenance'], 'titre' => 'pv.provenance', 'sous' => "''", 'routeEdit' => '/provenances/', 'routeListe' => '/provenances'],
            ['label' => 'Types de permis', 'perm' => 'types-permis.consulter', 'permMod' => 'types-permis.modifier', 'table' => 'types_permis', 'alias' => 'tp', 'agence' => false, 'colonnes' => ['nom'], 'titre' => 'tp.nom', 'sous' => "''", 'routeEdit' => '/types-permis/', 'routeListe' => '/types-permis'],
            ['label' => 'Types de prestations', 'perm' => 'types-prestations.consulter', 'permMod' => 'types-prestations.modifier', 'table' => 'types_prestations', 'alias' => 'tps', 'agence' => false, 'colonnes' => ['nom'], 'titre' => 'tps.nom', 'sous' => "''", 'routeEdit' => '/types-prestations/', 'routeListe' => '/types-prestations'],
            ['label' => 'Phrases d\'ouverture', 'perm' => 'phrases-ouverture.consulter', 'permMod' => 'phrases-ouverture.modifier', 'table' => 'phrase_ouverture', 'alias' => 'ph', 'agence' => false, 'supprimer' => false, 'colonnes' => ['phrase'], 'titre' => 'ph.phrase', 'sous' => "''", 'routeEdit' => '/phrases-ouverture/', 'routeListe' => '/phrases-ouverture'],
                   ['label' => 'Prospects', 'perm' => 'prospects.consulter', 'permMod' => 'prospects.modifier', 'table' => 'prospects', 'alias' => 'pr', 'agence' => true, 'colonnes' => ['nom', 'prenom', 'email', 'telephone'], 'titre' => "CONCAT_WS(' ', pr.prenom, pr.nom)", 'sous' => 'pr.email', 'routeEdit' => '/prospects/', 'routeListe' => '/prospects'],
            ['label' => 'Élèves', 'perm' => 'eleves.consulter', 'permMod' => 'eleves.modifier', 'table' => 'eleves', 'alias' => 'el', 'agence' => true, 'colonnes' => ['nom', 'prenom', 'email', 'telephone'], 'titre' => "CONCAT_WS(' ', el.prenom, el.nom)", 'sous' => 'el.email', 'routeEdit' => '/eleves/', 'routeListe' => '/eleves'],
        ];

        $sections = [];
        foreach ($defs as $d) {
            if (!Gate::can($d['perm'])) {
                continue;
            }

            $prefix = $d['alias'] . '.';
            $params = ['tenant' => $tid];
            $where = $prefix . 'tenant_id = :tenant';
            if (($d['supprimer'] ?? true)) {
                $where .= ' AND ' . $prefix . 'supprimer = 0';
            }
            if (!empty($d['agence']) && $scope !== null) {
                $where .= ' AND ' . $prefix . 'agence = :scope';
                $params['scope'] = $scope;
            }

            $ors = [];
            $i = 0;
            foreach ($d['colonnes'] as $colonne) {
                $i++;
                $ors[] = $prefix . $colonne . ' LIKE :q' . $i;
                $params['q' . $i] = $like;
            }
            $where .= ' AND (' . implode(' OR ', $ors) . ')';

            $from = $d['table'] . ' ' . $d['alias'];

            $lignes = Database::fetchAll(
                'SELECT ' . $prefix . 'id AS id, ' . $d['titre'] . ' AS titre, ' . $d['sous'] . ' AS sous
                 FROM ' . $from . ' WHERE ' . $where . ' ORDER BY titre LIMIT ' . self::LIMITE,
                $params
            );
            $row = Database::fetch('SELECT COUNT(*) AS n FROM ' . $from . ' WHERE ' . $where, $params);
            $total = (int)($row['n'] ?? 0);
            if ($total === 0) {
                continue;
            }

            $peutModifier = Gate::can($d['permMod']);
            $resultats = [];
            foreach ($lignes as $l) {
                $resultats[] = [
                    'titre' => (string)$l['titre'],
                    'sous'  => (string)($l['sous'] ?? ''),
                    'url'   => $peutModifier
                        ? url($d['routeEdit'] . (int)$l['id'] . '/modifier')
                        : url($d['routeListe'] . '?q=' . rawurlencode($q)),
                ];
            }

            $sections[] = [
                'label'    => $d['label'],
                'total'    => $total,
                'resultats'=> $resultats,
                'urlListe' => url($d['routeListe']) . '?q=' . rawurlencode($q),
            ];
        }

        return $sections;
    }
}