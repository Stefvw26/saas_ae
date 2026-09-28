-- =============================================================
-- JALON 2 — ADMINISTRATION ET FONDATIONS
-- Tables : roles, permissions, role_permissions, parametres, logs
-- + clé étrangère utilisateurs.role -> roles.code
-- =============================================================

CREATE TABLE `roles` (
    `id`         TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `code`       VARCHAR(30)  NOT NULL,
    `nom`        VARCHAR(50)  NOT NULL,
    `descriptif` VARCHAR(255) NULL,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `idx_roles_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `permissions` (
    `id`      SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `module`  VARCHAR(40)  NOT NULL,
    `code`    VARCHAR(60)  NOT NULL,
    `libelle` VARCHAR(150) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `idx_permissions_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `role_permissions` (
    `role_id`       TINYINT UNSIGNED  NOT NULL,
    `permission_id` SMALLINT UNSIGNED NOT NULL,
    PRIMARY KEY (`role_id`, `permission_id`),
    CONSTRAINT `fk_rp_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_rp_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `parametres` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id`   INT UNSIGNED NOT NULL,
    `cle`         VARCHAR(80) NOT NULL,
    `valeur`      TEXT        NULL,
    `type`        ENUM('entier','booleen','chaine') NOT NULL DEFAULT 'chaine',
    `modifie_le`  DATETIME    NULL,
    `modifie_par` INT UNSIGNED NULL,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `idx_parametres_tenant_cle` (`tenant_id`, `cle`),
    CONSTRAINT `fk_parametres_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `logs` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id`      INT UNSIGNED NULL,
    `utilisateur_id` INT UNSIGNED NULL,
    `login`          VARCHAR(80)  NULL,
    `action`         VARCHAR(80)  NOT NULL,
    `objet`          VARCHAR(60)  NULL,
    `objet_id`       INT UNSIGNED NULL,
    `agence_id`      INT UNSIGNED NULL,
    `resultat`       VARCHAR(20)  NOT NULL DEFAULT 'succes',
    `infos`          TEXT         NULL,
    `cree_le`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_logs_tenant_date` (`tenant_id`, `cree_le`),
    INDEX `idx_logs_utilisateur` (`utilisateur_id`),
    INDEX `idx_logs_action` (`action`),
    INDEX `idx_logs_agence` (`agence_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rôles (CDC §11)
INSERT INTO `roles` (`code`, `nom`, `descriptif`) VALUES
('visiteur',       'Visiteur',       'Consultation limitée à son agence'),
('employe',        'Employé',        'Accès limité à son agence selon ses droits'),
('moniteur',       'Moniteur',       'Moniteur enseignant'),
('directeur',      'Directeur',      'Droits étendus sur son agence'),
('administrateur', 'Administrateur', 'Droits globaux');

-- Registre des permissions (module.action)
INSERT INTO `permissions` (`module`, `code`, `libelle`) VALUES
('administration', 'administration.acceder', 'Accéder à l''administration'),
('administration', 'droits.consulter',       'Consulter les rôles et droits'),
('administration', 'droits.modifier',        'Modifier la matrice des droits'),
('administration', 'parametres.consulter',   'Consulter les paramètres'),
('administration', 'parametres.modifier',    'Modifier les paramètres'),
('administration', 'journaux.consulter',     'Consulter les journaux d''activité'),
('utilisateurs',   'utilisateurs.consulter', 'Consulter les utilisateurs'),
('utilisateurs',   'utilisateurs.creer',     'Créer un utilisateur'),
('utilisateurs',   'utilisateurs.modifier',  'Modifier un utilisateur'),
('utilisateurs',   'utilisateurs.supprimer', 'Supprimer (archiver) un utilisateur'),
('agences',        'agences.consulter',      'Consulter les agences'),
('agences',        'agences.creer',          'Créer une agence'),
('agences',        'agences.modifier',       'Modifier une agence'),
('agences',        'agences.supprimer',      'Supprimer (archiver) une agence'),
('agences',        'agences.changer',        'Changer d''agence active');

-- Matrice par défaut — lecture stricte du CDC §13 :
-- administrateur = droits globaux ; directeur = droits étendus sur son agence ;
-- visiteur / employé / moniteur = AUCUNE permission d'administration par défaut
-- (ajustable via l'interface Droits ou les droits personnalisés).
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.`id`, p.`id` FROM `roles` r JOIN `permissions` p WHERE r.`code` = 'administrateur';

INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.`id`, p.`id` FROM `roles` r JOIN `permissions` p
WHERE r.`code` = 'directeur'
  AND p.`code` IN (
    'administration.acceder', 'utilisateurs.consulter', 'utilisateurs.creer',
    'utilisateurs.modifier', 'agences.consulter', 'agences.modifier',
    'agences.changer', 'parametres.consulter', 'journaux.consulter'
  );

-- Rattachement des utilisateurs aux rôles (après insertion des rôles :
-- la contrainte vérifie les lignes existantes).
CREATE INDEX `idx_utilisateurs_role` ON `utilisateurs` (`role`);
ALTER TABLE `utilisateurs`
    ADD CONSTRAINT `fk_utilisateurs_role` FOREIGN KEY (`role`) REFERENCES `roles` (`code`) ON UPDATE CASCADE;