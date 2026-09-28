-- =============================================================
-- v0.22 — abandon de places (Domaines à la place), PNG icônes,
-- composant commentaires conversationnels (CDC §14/§16/§18/§33)
-- =============================================================

-- 1. Abandon du référentiel places (directive utilisateur)
DELETE rp FROM `role_permissions` rp
INNER JOIN `permissions` p ON p.id = rp.permission_id
WHERE p.`module` = 'places';

DELETE FROM `permissions` WHERE `module` = 'places';

DROP TABLE IF EXISTS `places`;

-- 2. Icônes partenaires : SVG -> PNG (fichiers fournis par l'utilisateur)
UPDATE `partenaires_type` SET `icone` = 'partenaire-auto-ecole.png' WHERE `icone` = 'partenaire-auto-ecole.svg';
UPDATE `partenaires_type` SET `icone` = 'partenaire-hotel.png'      WHERE `icone` = 'partenaire-hotel.svg';

-- 3. Commentaires conversationnels (table transverse unique couvrant
--    les structures CDC §14 [utilisateur], §16 [véhicule + km],
--    §18 [centre + partenaire] — décision documentée context.md)
CREATE TABLE `commentaires` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id`      INT UNSIGNED NOT NULL,
    `agence_id`      INT UNSIGNED NULL,
    `objet_type`     VARCHAR(30)  NOT NULL,
    `objet_id`       INT UNSIGNED NOT NULL,
    `km`             INT UNSIGNED NULL,
    `partenaire_id`  INT UNSIGNED NULL,
    `commentaire`    TEXT         NOT NULL,
    `utilisateur_id` INT UNSIGNED NULL,
    `cree_le`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_commentaires_objet` (`tenant_id`, `objet_type`, `objet_id`),
    INDEX `idx_commentaires_auteur` (`utilisateur_id`),
    INDEX `idx_commentaires_date` (`cree_le`),
    CONSTRAINT `fk_commentaires_tenant`     FOREIGN KEY (`tenant_id`)     REFERENCES `tenants` (`id`),
    CONSTRAINT `fk_commentaires_agence`     FOREIGN KEY (`agence_id`)     REFERENCES `agences` (`id`),
    CONSTRAINT `fk_commentaires_partenaire` FOREIGN KEY (`partenaire_id`) REFERENCES `partenaires` (`id`),
    CONSTRAINT `fk_commentaires_utilisateur` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `permissions` (`module`, `code`, `libelle`) VALUES
('commentaires', 'commentaires.creer', 'Ajouter des commentaires');

INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.`id`, p.`id` FROM `roles` r JOIN `permissions` p
WHERE r.`code` IN ('administrateur', 'directeur') AND p.`code` = 'commentaires.creer';