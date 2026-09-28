-- =============================================================
-- JALON 6 — ÉLÈVES (CDC §50-59)
-- §44 : AUCUN prospect_id (séparation stricte, symétrique)
-- =============================================================

CREATE TABLE `eleves` (
    `id`                   INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id`            INT UNSIGNED NOT NULL,
    `agence_id`            INT UNSIGNED NULL,
    `domaine_id`           INT UNSIGNED NULL,
    `provenance_id`        INT UNSIGNED NULL,
    `type_permis_id`       INT UNSIGNED NULL,
    `civilite`             VARCHAR(10)  NULL,
    `nom`                  VARCHAR(80)  NOT NULL,
    `prenom`               VARCHAR(80)  NULL,
    `date_naissance`       DATE         NULL,
    `email`                VARCHAR(190) NULL,
    `telephone`            VARCHAR(30)  NULL,
    `adresse`              VARCHAR(255) NULL,
    `code_postal`          VARCHAR(10)  NULL,
    `ville`                VARCHAR(80)  NULL,
    `pays`                 VARCHAR(80)  NULL,
    `type_boite`           VARCHAR(10)  NULL,
    `type_b`               VARCHAR(5)   NULL,
    `responsable_nom`      VARCHAR(80)  NULL,
    `responsable_telephone` VARCHAR(30) NULL,
    `responsable_email`    VARCHAR(190) NULL,
    `actif`                TINYINT(1)   NOT NULL DEFAULT 1,
    `ajout_le`             DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `ajout_par`            INT UNSIGNED NULL,
    `supprimer`            TINYINT(1)   NOT NULL DEFAULT 0,
    `supprimer_par`        INT UNSIGNED NULL,
    `supprimer_date`       DATETIME     NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_eleves_tenant_statut` (`tenant_id`, `supprimer`),
    INDEX `idx_eleves_agence` (`agence_id`),
    INDEX `idx_eleves_domaine` (`domaine_id`),
    INDEX `idx_eleves_type_permis` (`type_permis_id`),
    CONSTRAINT `fk_eleves_tenant`      FOREIGN KEY (`tenant_id`)      REFERENCES `tenants` (`id`),
    CONSTRAINT `fk_eleves_agence`      FOREIGN KEY (`agence_id`)     REFERENCES `agences` (`id`),
    CONSTRAINT `fk_eleves_domaine`     FOREIGN KEY (`domaine_id`)    REFERENCES `domaines` (`id`),
    CONSTRAINT `fk_eleves_provenance`  FOREIGN KEY (`provenance_id`) REFERENCES `provenances` (`id`),
    CONSTRAINT `fk_eleves_type_permis` FOREIGN KEY (`type_permis_id`) REFERENCES `types_permis` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `permissions` (`module`, `code`, `libelle`) VALUES
('eleves', 'eleves.consulter', 'Consulter les élèves'),
('eleves', 'eleves.creer',     'Créer un élève'),
('eleves', 'eleves.modifier',  'Modifier un élève'),
('eleves', 'eleves.supprimer', 'Archiver un élève');

INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.`id`, p.`id` FROM `roles` r JOIN `permissions` p
WHERE r.`code` = 'administrateur' AND p.`module` = 'eleves';

INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.`id`, p.`id` FROM `roles` r JOIN `permissions` p
WHERE r.`code` = 'directeur' AND p.`code` IN ('eleves.consulter', 'eleves.creer', 'eleves.modifier');