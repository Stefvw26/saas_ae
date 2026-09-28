-- =============================================================
-- v0.49 — MODULE FORMULES (directive utilisateur)
-- Une formule regroupe des prestations ; son montant TTC =
-- total des prestations (calculé dynamiquement).
-- Formules rattachées à un domaine (lieu de formation).
-- =============================================================

CREATE TABLE `formules` (
    `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id`      INT UNSIGNED NOT NULL,
    `domaine_id`     INT UNSIGNED NULL,
    `nom`            VARCHAR(150) NOT NULL,
    `descriptif`     VARCHAR(500) NULL,
    `actif`          TINYINT(1)   NOT NULL DEFAULT 1,
    `ajout_le`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `ajout_par`      INT UNSIGNED NULL,
    `supprimer`      TINYINT(1)   NOT NULL DEFAULT 0,
    `supprimer_par`  INT UNSIGNED NULL,
    `supprimer_date` DATETIME     NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_formules_tenant` (`tenant_id`, `supprimer`),
    INDEX `idx_formules_domaine` (`domaine_id`),
    CONSTRAINT `fk_formules_tenant`  FOREIGN KEY (`tenant_id`)  REFERENCES `tenants` (`id`),
    CONSTRAINT `fk_formules_domaine` FOREIGN KEY (`domaine_id`) REFERENCES `domaines` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `formule_prestations` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `formule_id`    INT UNSIGNED NOT NULL,
    `prestation_id` INT UNSIGNED NOT NULL,
    `quantite`      INT UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `idx_fp_unique` (`formule_id`, `prestation_id`),
    CONSTRAINT `fk_fp_formule`    FOREIGN KEY (`formule_id`)    REFERENCES `formules` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_fp_prestation` FOREIGN KEY (`prestation_id`) REFERENCES `prestations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `permissions` (`module`, `code`, `libelle`) VALUES
('formules', 'formules.consulter', 'Consulter les formules'),
('formules', 'formules.creer',     'Créer une formule'),
('formules', 'formules.modifier',  'Modifier une formule'),
('formules', 'formules.supprimer', 'Supprimer (archiver) une formule');

INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.`id`, p.`id` FROM `roles` r JOIN `permissions` p
WHERE r.`code` = 'administrateur' AND p.`module` = 'formules';

INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.`id`, p.`id` FROM `roles` r JOIN `permissions` p
WHERE r.`code` = 'directeur' AND p.`code` IN ('formules.consulter', 'formules.creer', 'formules.modifier');