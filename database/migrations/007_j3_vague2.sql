-- =============================================================
-- JALON 3 — RÉFÉRENTIELS (2e vague)
-- vehicules (CDC §15), centres (§17), partenaires (§19),
-- prestations (§20) — + tenant_id (isolation SaaS, CDC §7)
-- Sémantique des champs ambigus (mécanique, état, statut, og,
-- flags prestations) : VARCHAR / TINYINT — points ouverts documentés.
-- =============================================================

CREATE TABLE `vehicules` (
    `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id`      INT UNSIGNED NOT NULL,
    `agence`         INT UNSIGNED NULL,
    `immatriculation` VARCHAR(20) NOT NULL,
    `marque`         VARCHAR(80)  NOT NULL,
    `model`          VARCHAR(80)  NULL,
    `mecanique`      VARCHAR(80)  NULL,
    `couleur`        VARCHAR(7)   NULL,
    `etat`           VARCHAR(80)  NULL,
    `statut`         VARCHAR(80)  NULL,
    `actif`          TINYINT(1)   NOT NULL DEFAULT 1,
    `ajout_le`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `ajout_par`      INT UNSIGNED NULL,
    `supprimer`      TINYINT(1)   NOT NULL DEFAULT 0,
    `supprimer_par`  INT UNSIGNED NULL,
    `supprimer_date` DATETIME     NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_vehicules_tenant` (`tenant_id`),
    INDEX `idx_vehicules_agence` (`agence`),
    CONSTRAINT `fk_vehicules_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`),
    CONSTRAINT `fk_vehicules_agence` FOREIGN KEY (`agence`) REFERENCES `agences` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `centres` (
    `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id`      INT UNSIGNED NOT NULL,
    `url`            VARCHAR(255) NULL,
    `nom`            VARCHAR(80)  NOT NULL,
    `descriptif`     VARCHAR(255) NULL,
    `ville`          VARCHAR(80)  NULL,
    `pays`           VARCHAR(80)  NULL,
    `code_postal`    VARCHAR(10)  NULL,
    `departement`    VARCHAR(80)  NULL,
    `latitude`       DECIMAL(10,7) NULL,
    `longitude`      DECIMAL(10,7) NULL,
    `photographie`   VARCHAR(255) NULL,
    `actif`          TINYINT(1)   NOT NULL DEFAULT 1,
    `ajout_le`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `ajout_par`      INT UNSIGNED NULL,
    `supprimer`      TINYINT(1)   NOT NULL DEFAULT 0,
    `supprimer_par`  INT UNSIGNED NULL,
    `supprimer_date` DATETIME     NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_centres_tenant` (`tenant_id`),
    CONSTRAINT `fk_centres_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `partenaires` (
    `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id`           INT UNSIGNED NOT NULL,
    `nom`                 VARCHAR(150) NOT NULL,
    `type`                VARCHAR(80)  NULL,
    `url`                 VARCHAR(255) NULL,
    `telephone`           VARCHAR(30)  NULL,
    `descriptif`          VARCHAR(255) NULL,
    `hotel_etoile`        TINYINT UNSIGNED NULL,
    `adresse`             VARCHAR(255) NULL,
    `centre_id`           INT UNSIGNED NULL,
    `latitude`            DECIMAL(10,7) NULL,
    `longitude`           DECIMAL(10,7) NULL,
    `photographie`        VARCHAR(255) NULL,
    `og`                  VARCHAR(255) NULL,
    `agreement`           VARCHAR(100) NULL,
    `date_agreement`      DATE         NULL,
    `agrement_exploitant` VARCHAR(150) NULL,
    `assurance`           VARCHAR(150) NULL,
    `actif`               TINYINT(1)   NOT NULL DEFAULT 1,
    `ajout_le`            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `ajout_par`           INT UNSIGNED NULL,
    `supprimer`           TINYINT(1)   NOT NULL DEFAULT 0,
    `supprimer_par`       INT UNSIGNED NULL,
    `supprimer_date`      DATETIME     NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_partenaires_tenant` (`tenant_id`),
    INDEX `idx_partenaires_centre` (`centre_id`),
    CONSTRAINT `fk_partenaires_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`),
    CONSTRAINT `fk_partenaires_centre` FOREIGN KEY (`centre_id`) REFERENCES `centres` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `prestations` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id`     INT UNSIGNED NOT NULL,
    `titre`         VARCHAR(150) NOT NULL,
    `description`   TEXT         NULL,
    `prix_vente`    DECIMAL(10,2) NULL,
    `prix_achete`   DECIMAL(10,2) NULL,
    `type_id`       INT UNSIGNED NULL,
    `administratif` TINYINT(1)   NOT NULL DEFAULT 0,
    `sejour`        TINYINT(1)   NOT NULL DEFAULT 0,
    `assurance`     TINYINT(1)   NOT NULL DEFAULT 0,
    `sku`           VARCHAR(50)  NULL,
    `dans_contrat`  TINYINT(1)   NOT NULL DEFAULT 0,
    `actif`         TINYINT(1)   NOT NULL DEFAULT 1,
    `ajout_le`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `ajout_par`     INT UNSIGNED NULL,
    `supprimer`     TINYINT(1)   NOT NULL DEFAULT 0,
    `supprimer_par` INT UNSIGNED NULL,
    `supprimer_date` DATETIME    NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_prestations_tenant` (`tenant_id`),
    INDEX `idx_prestations_type` (`type_id`),
    CONSTRAINT `fk_prestations_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`),
    CONSTRAINT `fk_prestations_type` FOREIGN KEY (`type_id`) REFERENCES `types_prestations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `permissions` (`module`, `code`, `libelle`) VALUES
('vehicules',   'vehicules.consulter',   'Consulter les véhicules'),
('vehicules',   'vehicules.creer',       'Créer un véhicule'),
('vehicules',   'vehicules.modifier',    'Modifier un véhicule'),
('vehicules',   'vehicules.supprimer',   'Supprimer (archiver) un véhicule'),
('centres',     'centres.consulter',     'Consulter les centres'),
('centres',     'centres.creer',         'Créer un centre'),
('centres',     'centres.modifier',      'Modifier un centre'),
('centres',     'centres.supprimer',     'Supprimer (archiver) un centre'),
('partenaires', 'partenaires.consulter', 'Consulter les partenaires'),
('partenaires', 'partenaires.creer',     'Créer un partenaire'),
('partenaires', 'partenaires.modifier',  'Modifier un partenaire'),
('partenaires', 'partenaires.supprimer', 'Supprimer (archiver) un partenaire'),
('prestations', 'prestations.consulter', 'Consulter les prestations'),
('prestations', 'prestations.creer',     'Créer une prestation'),
('prestations', 'prestations.modifier',  'Modifier une prestation'),
('prestations', 'prestations.supprimer', 'Supprimer (archiver) une prestation');

INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.`id`, p.`id` FROM `roles` r JOIN `permissions` p
WHERE r.`code` = 'administrateur' AND p.`module` IN ('vehicules', 'centres', 'partenaires', 'prestations');

INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.`id`, p.`id` FROM `roles` r JOIN `permissions` p
WHERE r.`code` = 'directeur' AND p.`code` IN (
    'vehicules.consulter', 'centres.consulter',
    'partenaires.consulter', 'prestations.consulter'
);