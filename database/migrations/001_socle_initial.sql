-- fichier : database/migrations/001_socle_initial.sql
-- =============================================================
-- JALON 1 — SOCLE MVC
-- Tables initiales : tenants, agences, utilisateurs,
-- login_attempts, migrations
-- =============================================================

CREATE TABLE `tenants` (
    `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nom`            VARCHAR(150) NOT NULL,
    `actif`          TINYINT(1)   NOT NULL DEFAULT 1,
    `ajout_le`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `supprimer`      TINYINT(1)   NOT NULL DEFAULT 0,
    `supprimer_par`  INT UNSIGNED NULL,
    `supprimer_date` DATETIME     NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Structure conforme au cahier des charges §8 + identifiant de tenant
CREATE TABLE `agences` (
    `id`                          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `tenant_id`                   INT UNSIGNED  NOT NULL,
    `domaine`                     VARCHAR(100)  NULL,
    `agence_nom`                  VARCHAR(150)  NOT NULL,
    `agence_adresse`              VARCHAR(255)  NULL,
    `agence_telephone`            VARCHAR(30)   NULL,
    `agence_agreement`            VARCHAR(100)  NULL,
    `agence_agreement_date`       DATE          NULL,
    `agence_agreement_exploitant` VARCHAR(150)  NULL,
    `agence_assurance`            VARCHAR(150)  NULL,
    `agence_couleur`              VARCHAR(7)    NULL,
    `latitude`                    DECIMAL(10,7) NULL,
    `longitude`                   DECIMAL(10,7) NULL,
    `domaine_initiale`            VARCHAR(10)   NULL,
    `ajout_le`                    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `ajout_par`                   INT UNSIGNED  NULL,
    `supprimer`                   TINYINT(1)    NOT NULL DEFAULT 0,
    `supprimer_par`               INT UNSIGNED  NULL,
    `supprimer_date`              DATETIME      NULL,
    `actif`                       TINYINT(1)    NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    INDEX `idx_agences_tenant` (`tenant_id`),
    CONSTRAINT `fk_agences_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Structure conforme au cahier des charges §10 + identifiant de tenant
-- `droit` : réservé (permissions détaillées — Jalon 2)
CREATE TABLE `utilisateurs` (
    `id`                INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `tenant_id`         INT UNSIGNED  NOT NULL,
    `login`             VARCHAR(80)   NOT NULL,
    `mot_de_passe`      VARCHAR(255)  NOT NULL,
    `mail`              VARCHAR(190)  NULL,
    `nom`               VARCHAR(80)   NOT NULL,
    `prenom`            VARCHAR(80)   NULL,
    `couleur`           VARCHAR(7)    NULL,
    `droit`             TEXT          NULL,
    `role`              VARCHAR(30)   NOT NULL DEFAULT 'visiteur',
    `est_un_enseignant` TINYINT(1)    NOT NULL DEFAULT 0,
    `agence`            INT UNSIGNED  NULL,
    `tous_droit`        TINYINT(1)    NOT NULL DEFAULT 0,
    `photographie`      VARCHAR(255)  NULL,
    `actif`             TINYINT(1)    NOT NULL DEFAULT 1,
    `date_de_naissance` DATE          NULL,
    `civilite`          VARCHAR(10)   NULL,
    `ajout_le`          DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `ajout_par`         INT UNSIGNED  NULL,
    `supprimer`         TINYINT(1)    NOT NULL DEFAULT 0,
    `supprimer_par`     INT UNSIGNED  NULL,
    `supprimer_date`    DATETIME      NULL,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `idx_utilisateurs_login` (`login`),
    INDEX `idx_utilisateurs_tenant` (`tenant_id`),
    INDEX `idx_utilisateurs_agence` (`agence`),
    CONSTRAINT `fk_utilisateurs_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`),
    CONSTRAINT `fk_utilisateurs_agence` FOREIGN KEY (`agence`) REFERENCES `agences` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Anti brute-force (nécessité technique de sécurité)
CREATE TABLE `login_attempts` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `identifiant`  VARCHAR(190)    NOT NULL,
    `ip`           VARCHAR(45)     NOT NULL,
    `succes`       TINYINT(1)      NOT NULL DEFAULT 0,
    `tentative_le` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_login_attempts_identifiant` (`identifiant`),
    INDEX `idx_login_attempts_ip` (`ip`),
    INDEX `idx_login_attempts_date` (`tentative_le`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Suivi des migrations exécutées (IF NOT EXISTS : pré-créée par migrate.php)
CREATE TABLE IF NOT EXISTS `migrations` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `fichier`    VARCHAR(190) NOT NULL,
    `execute_le` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `idx_migrations_fichier` (`fichier`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;