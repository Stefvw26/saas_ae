-- =============================================================
-- JALON 5 — PROSPECTS (CDC §37-49)
-- §44 : champs de conversion SANS eleve_id (décision conservée)
-- =============================================================

CREATE TABLE `prospects` (
    `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id`      INT UNSIGNED NOT NULL,
    `agence_id`      INT UNSIGNED NULL,
    `domaine_id`     INT UNSIGNED NULL,
    `nom`            VARCHAR(80)  NOT NULL,
    `prenom`         VARCHAR(80)  NULL,
    `date_naissance` DATE         NULL,
    `email`          VARCHAR(190) NULL,
    `telephone`      VARCHAR(30)  NULL,
    `adresse`        VARCHAR(255) NULL,
    `code_postal`    VARCHAR(10)  NULL,
    `ville`          VARCHAR(80)  NULL,
    `pays`           VARCHAR(80)  NULL,
    `type_permis_id` INT UNSIGNED NULL,
    `parcours`       TEXT         NULL,
    `commentaire`    TEXT         NULL,
    `provenance_id`  INT UNSIGNED NULL,
    `statut`         ENUM('nouveau','traite','archive') NOT NULL DEFAULT 'nouveau',
    `motif_archivage` VARCHAR(255) NULL,
    `convertis`      TINYINT(1)   NOT NULL DEFAULT 0,
    `convertis_par`  INT UNSIGNED NULL,
    `convertis_date` DATETIME     NULL,
    `ajout_le`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `ajout_par`      INT UNSIGNED NULL,
    `supprimer`      TINYINT(1)   NOT NULL DEFAULT 0,
    `supprimer_par`  INT UNSIGNED NULL,
    `supprimer_date` DATETIME     NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_prospects_tenant_statut` (`tenant_id`, `statut`),
    INDEX `idx_prospects_agence` (`agence_id`),
    INDEX `idx_prospects_domaine` (`domaine_id`),
    INDEX `idx_prospects_type_permis` (`type_permis_id`),
    CONSTRAINT `fk_prospects_tenant`      FOREIGN KEY (`tenant_id`)      REFERENCES `tenants` (`id`),
    CONSTRAINT `fk_prospects_agence`      FOREIGN KEY (`agence_id`)      REFERENCES `agences` (`id`),
    CONSTRAINT `fk_prospects_domaine`     FOREIGN KEY (`domaine_id`)     REFERENCES `domaines` (`id`),
    CONSTRAINT `fk_prospects_type_permis` FOREIGN KEY (`type_permis_id`) REFERENCES `types_permis` (`id`),
    CONSTRAINT `fk_prospects_provenance`  FOREIGN KEY (`provenance_id`)  REFERENCES `provenances` (`id`),
    CONSTRAINT `fk_prospects_convertis_par` FOREIGN KEY (`convertis_par`) REFERENCES `utilisateurs` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `permissions` (`module`, `code`, `libelle`) VALUES
('prospects', 'prospects.consulter', 'Consulter les prospects'),
('prospects', 'prospects.creer',     'Créer un prospect'),
('prospects', 'prospects.modifier',  'Modifier un prospect'),
('prospects', 'prospects.traiter',   'Traiter un prospect'),
('prospects', 'prospects.archiver',  'Archiver un prospect'),
('prospects', 'prospects.convertir', 'Convertir un prospect en élève');

INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.`id`, p.`id` FROM `roles` r JOIN `permissions` p
WHERE r.`code` = 'administrateur' AND p.`module` = 'prospects';

INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.`id`, p.`id` FROM `roles` r JOIN `permissions` p
WHERE r.`code` = 'directeur' AND p.`code` IN (
    'prospects.consulter', 'prospects.creer', 'prospects.traiter'
);