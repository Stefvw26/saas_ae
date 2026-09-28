-- =============================================================
-- J3 (ouverture) / FINALISATION J2 — DIRECTIVES UTILISATEUR
-- 1. Table domaines (CRUD)
-- 2. utilisateurs : telephone (obligatoire en saisie), domaine_id
-- 3. Permissions du module domaines
-- =============================================================

CREATE TABLE `domaines` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id`     INT UNSIGNED NOT NULL,
    `nom`           VARCHAR(150) NOT NULL,
    `descriptif`    VARCHAR(255) NULL,
    `initiale`      VARCHAR(10)  NULL,
    `actif`         TINYINT(1)   NOT NULL DEFAULT 1,
    `ajout_le`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `ajout_par`     INT UNSIGNED NULL,
    `supprimer`     TINYINT(1)   NOT NULL DEFAULT 0,
    `supprimer_par` INT UNSIGNED NULL,
    `supprimer_date` DATETIME    NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_domaines_tenant` (`tenant_id`),
    CONSTRAINT `fk_domaines_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `utilisateurs`
    ADD COLUMN `telephone`  VARCHAR(30)   NULL AFTER `mail`,
    ADD COLUMN `domaine_id` INT UNSIGNED  NULL AFTER `agence`;

ALTER TABLE `utilisateurs`
    ADD INDEX `idx_utilisateurs_domaine` (`domaine_id`),
    ADD CONSTRAINT `fk_utilisateurs_domaine` FOREIGN KEY (`domaine_id`) REFERENCES `domaines` (`id`);

INSERT INTO `permissions` (`module`, `code`, `libelle`) VALUES
('domaines', 'domaines.consulter',  'Consulter les domaines'),
('domaines', 'domaines.creer',      'Créer un domaine'),
('domaines', 'domaines.modifier',   'Modifier un domaine'),
('domaines', 'domaines.supprimer',  'Supprimer (archiver) un domaine');

INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.`id`, p.`id` FROM `roles` r JOIN `permissions` p
WHERE r.`code` = 'administrateur' AND p.`module` = 'domaines';

INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.`id`, p.`id` FROM `roles` r JOIN `permissions` p
WHERE r.`code` = 'directeur' AND p.`code` = 'domaines.consulter';