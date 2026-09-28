-- =============================================================
-- JALON 3 — RÉFÉRENTIELS (1re vague)
-- provenances (CDC §23), types_permis (§22), types_prestations (§21),
-- phrase_ouverture (§24) — + tenant_id (isolation SaaS, CDC §7)
-- =============================================================

CREATE TABLE `provenances` (
    `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id`      INT UNSIGNED NOT NULL,
    `provenance`     VARCHAR(150) NOT NULL,
    `actif`          TINYINT(1)   NOT NULL DEFAULT 1,
    `ajout_le`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `ajout_par`      INT UNSIGNED NULL,
    `supprimer`      TINYINT(1)   NOT NULL DEFAULT 0,
    `supprimer_par`  INT UNSIGNED NULL,
    `supprimer_date` DATETIME     NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_provenances_tenant` (`tenant_id`),
    CONSTRAINT `fk_provenances_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `types_permis` (
    `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id`      INT UNSIGNED NOT NULL,
    `nom`            VARCHAR(80)  NOT NULL,
    `descriptif`     VARCHAR(255) NULL,
    `initiale`       VARCHAR(10)  NULL,
    `actif`          TINYINT(1)   NOT NULL DEFAULT 1,
    `ajout_le`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `ajout_par`      INT UNSIGNED NULL,
    `supprimer`      TINYINT(1)   NOT NULL DEFAULT 0,
    `supprimer_par`  INT UNSIGNED NULL,
    `supprimer_date` DATETIME     NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_types_permis_tenant` (`tenant_id`),
    CONSTRAINT `fk_types_permis_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pas d'unicité sur nom : le CDC §21 liste un doublon « DIVERS »
-- (point ouvert explicitement signalé par le cahier des charges).
CREATE TABLE `types_prestations` (
    `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id`      INT UNSIGNED NOT NULL,
    `nom`            VARCHAR(100) NOT NULL,
    `descriptif`     VARCHAR(255) NULL,
    `actif`          TINYINT(1)   NOT NULL DEFAULT 1,
    `ajout_le`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `ajout_par`      INT UNSIGNED NULL,
    `supprimer`      TINYINT(1)   NOT NULL DEFAULT 0,
    `supprimer_par`  INT UNSIGNED NULL,
    `supprimer_date` DATETIME     NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_types_prestations_tenant` (`tenant_id`),
    CONSTRAINT `fk_types_prestations_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CDC §24 : champs id, phrase, ajout_par, ajout_date, actif
-- (pas de suppression logique prévue pour cette table -> suppression définitive).
CREATE TABLE `phrase_ouverture` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id`  INT UNSIGNED NOT NULL,
    `phrase`     VARCHAR(500) NOT NULL,
    `actif`      TINYINT(1)   NOT NULL DEFAULT 1,
    `ajout_date` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `ajout_par`  INT UNSIGNED NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_phrase_ouverture_tenant` (`tenant_id`),
    CONSTRAINT `fk_phrase_ouverture_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `permissions` (`module`, `code`, `libelle`) VALUES
('provenances',        'provenances.consulter',        'Consulter les provenances'),
('provenances',        'provenances.creer',            'Créer une provenance'),
('provenances',        'provenances.modifier',         'Modifier une provenance'),
('provenances',        'provenances.supprimer',        'Supprimer (archiver) une provenance'),
('types-permis',       'types-permis.consulter',       'Consulter les types de permis'),
('types-permis',       'types-permis.creer',           'Créer un type de permis'),
('types-permis',       'types-permis.modifier',        'Modifier un type de permis'),
('types-permis',       'types-permis.supprimer',       'Supprimer (archiver) un type de permis'),
('types-prestations',  'types-prestations.consulter',  'Consulter les types de prestations'),
('types-prestations',  'types-prestations.creer',      'Créer un type de prestation'),
('types-prestations',  'types-prestations.modifier',   'Modifier un type de prestation'),
('types-prestations',  'types-prestations.supprimer',  'Supprimer (archiver) un type de prestation'),
('phrases-ouverture',  'phrases-ouverture.consulter',  'Consulter les phrases d''ouverture'),
('phrases-ouverture',  'phrases-ouverture.creer',      'Créer une phrase d''ouverture'),
('phrases-ouverture',  'phrases-ouverture.modifier',   'Modifier une phrase d''ouverture'),
('phrases-ouverture',  'phrases-ouverture.supprimer',  'Supprimer une phrase d''ouverture');

INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.`id`, p.`id` FROM `roles` r JOIN `permissions` p
WHERE r.`code` = 'administrateur' AND p.`module` IN
    ('provenances', 'types-permis', 'types-prestations', 'phrases-ouverture');

INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.`id`, p.`id` FROM `roles` r JOIN `permissions` p
WHERE r.`code` = 'directeur' AND p.`code` IN (
    'provenances.consulter', 'types-permis.consulter',
    'types-prestations.consulter', 'phrases-ouverture.consulter'
);