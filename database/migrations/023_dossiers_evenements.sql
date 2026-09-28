-- =============================================================
-- JALON 7 — DOSSIERS ET ÉVÉNEMENTS (CDC §26, §55, §56, §58)
-- Statuts libres (en_cours/valide/confirme) — neutres tant que les
-- règles métier exactes ne sont pas validées par l'utilisateur.
-- =============================================================

CREATE TABLE `dossiers` (
    `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id`       INT UNSIGNED NOT NULL,
    `eleve_id`        INT UNSIGNED NOT NULL,
    `agence_id`       INT UNSIGNED NULL,
    `statut`          ENUM('en_cours','valide','confirme') NOT NULL DEFAULT 'en_cours',
    `nb_heures`       DECIMAL(6,1) NULL,
    `montant_total`   DECIMAL(10,2) NULL,
    `valide_le`       DATETIME NULL,
    `confirme_le`     DATETIME NULL,
    `notes`           TEXT NULL,
    `actif`           TINYINT(1) NOT NULL DEFAULT 1,
    `ajout_le`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `ajout_par`       INT UNSIGNED NULL,
    `supprimer`       TINYINT(1) NOT NULL DEFAULT 0,
    `supprimer_par`   INT UNSIGNED NULL,
    `supprimer_date`  DATETIME NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_dossiers_tenant` (`tenant_id`, `supprimer`),
    INDEX `idx_dossiers_eleve` (`eleve_id`),
    INDEX `idx_dossiers_agence` (`agence_id`),
    CONSTRAINT `fk_dossiers_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`),
    CONSTRAINT `fk_dossiers_eleve`  FOREIGN KEY (`eleve_id`)  REFERENCES `eleves` (`id`),
    CONSTRAINT `fk_dossiers_agence` FOREIGN KEY (`agence_id`) REFERENCES `agences` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `evenements` (
    `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id`       INT UNSIGNED NOT NULL,
    `dossier_id`      INT UNSIGNED NOT NULL,
    `type`            VARCHAR(80) NULL,
    `titre`           VARCHAR(255) NOT NULL,
    `date_heure`      DATETIME NOT NULL,
    `duree_minutes`   SMALLINT UNSIGNED NULL,
    `moniteur_id`     INT UNSIGNED NULL,
    `vehicule_id`     INT UNSIGNED NULL,
    `notes`           TEXT NULL,
    `ajout_le`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `ajout_par`       INT UNSIGNED NULL,
    `supprimer`       TINYINT(1) NOT NULL DEFAULT 0,
    `supprimer_par`   INT UNSIGNED NULL,
    `supprimer_date`  DATETIME NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_evenements_dossier` (`dossier_id`),
    INDEX `idx_evenements_date` (`date_heure`),
    CONSTRAINT `fk_evenements_tenant`  FOREIGN KEY (`tenant_id`)  REFERENCES `tenants` (`id`),
    CONSTRAINT `fk_evenements_dossier` FOREIGN KEY (`dossier_id`) REFERENCES `dossiers` (`id`),
    CONSTRAINT `fk_evenements_moniteur` FOREIGN KEY (`moniteur_id`) REFERENCES `utilisateurs` (`id`),
    CONSTRAINT `fk_evenements_vehicule` FOREIGN KEY (`vehicule_id`) REFERENCES `vehicules` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `permissions` (`module`, `code`, `libelle`) VALUES
('dossiers', 'dossiers.consulter', 'Consulter les dossiers'),
('dossiers', 'dossiers.creer',     'Créer un dossier'),
('dossiers', 'dossiers.modifier',  'Modifier un dossier'),
('dossiers', 'dossiers.supprimer', 'Archiver un dossier');

INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.`id`, p.`id` FROM `roles` r JOIN `permissions` p
WHERE r.`code` = 'administrateur' AND p.`module` = 'dossiers';

INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.`id`, p.`id` FROM `roles` r JOIN `permissions` p
WHERE r.`code` = 'directeur' AND p.`code` IN ('dossiers.consulter', 'dossiers.creer', 'dossiers.modifier');