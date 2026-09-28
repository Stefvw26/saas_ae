-- =============================================================
-- v0.20 — places, partenaires_type, adresses découpées,
-- couleur véhicules texte libre, flags prestations
-- =============================================================

-- Places (CDC §9) — rattachement dossier_id PRÉVU AU JALON 7 (dossiers)
CREATE TABLE `places` (
    `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id`      INT UNSIGNED NOT NULL,
    `libelle`        VARCHAR(150) NOT NULL,
    `couleur`        VARCHAR(7)   NULL,
    `actif`          TINYINT(1)   NOT NULL DEFAULT 1,
    `ajout_le`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `ajout_par`      INT UNSIGNED NULL,
    `supprimer`      TINYINT(1)   NOT NULL DEFAULT 0,
    `supprimer_par`  INT UNSIGNED NULL,
    `supprimer_date` DATETIME     NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_places_tenant` (`tenant_id`),
    CONSTRAINT `fk_places_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Types de partenaires (directive v0.20) — ajout_date demandé par l'utilisateur
CREATE TABLE `partenaires_type` (
    `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id`      INT UNSIGNED NOT NULL,
    `nom`            VARCHAR(150) NOT NULL,
    `descriptif`     VARCHAR(255) NULL,
    `actif`          TINYINT(1)   NOT NULL DEFAULT 1,
    `ajout_date`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `ajout_par`      INT UNSIGNED NULL,
    `supprimer`      TINYINT(1)   NOT NULL DEFAULT 0,
    `supprimer_par`  INT UNSIGNED NULL,
    `supprimer_date` DATETIME     NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_partenaires_type_tenant` (`tenant_id`),
    CONSTRAINT `fk_partenaires_type_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Véhicules : couleur en texte libre (blanche, noire…)
ALTER TABLE `vehicules` MODIFY COLUMN `couleur` VARCHAR(80) NULL;

-- Partenaires : type en FK, adresse découpée
ALTER TABLE `partenaires`
    ADD COLUMN `type_id`    INT UNSIGNED NULL AFTER `type`,
    ADD COLUMN `code_postal` VARCHAR(10) NULL AFTER `adresse`,
    ADD COLUMN `ville`      VARCHAR(80)  NULL AFTER `code_postal`,
    ADD COLUMN `pays`       VARCHAR(80)  NULL AFTER `ville`;

ALTER TABLE `partenaires`
    ADD INDEX `idx_partenaires_type_id` (`type_id`),
    ADD CONSTRAINT `fk_partenaires_type` FOREIGN KEY (`type_id`) REFERENCES `partenaires_type` (`id`);

ALTER TABLE `partenaires` DROP COLUMN `type`;

-- Prestations : flags calendrier et fournisseur (directive v0.20)
ALTER TABLE `prestations`
    ADD COLUMN `calendrier`  TINYINT(1) NOT NULL DEFAULT 0 AFTER `assurance`,
    ADD COLUMN `fournisseur` TINYINT(1) NOT NULL DEFAULT 0 AFTER `calendrier`;

INSERT INTO `permissions` (`module`, `code`, `libelle`) VALUES
('places', 'places.consulter',  'Consulter les places'),
('places', 'places.creer',      'Créer une place'),
('places', 'places.modifier',   'Modifier une place'),
('places', 'places.supprimer',  'Supprimer (archiver) une place');

INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.`id`, p.`id` FROM `roles` r JOIN `permissions` p
WHERE r.`code` = 'administrateur' AND p.`module` = 'places';

INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.`id`, p.`id` FROM `roles` r JOIN `permissions` p
WHERE r.`code` = 'directeur' AND p.`code` = 'places.consulter';