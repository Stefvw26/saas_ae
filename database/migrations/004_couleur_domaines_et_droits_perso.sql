-- =============================================================
-- DIRECTIVES UTILISATEUR — couleur domaines + droits personnalisés
-- =============================================================

ALTER TABLE `domaines`
    ADD COLUMN `couleur` VARCHAR(7) NULL AFTER `initiale`;

INSERT INTO `permissions` (`module`, `code`, `libelle`) VALUES
('administration', 'droits.creer', 'Créer des droits personnalisés');

INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.`id`, p.`id` FROM `roles` r JOIN `permissions` p
WHERE r.`code` = 'administrateur' AND p.`code` = 'droits.creer';