-- =============================================================
-- v0.16 — rôles personnalisés, agences/utilisateurs sans domaine
-- =============================================================

-- 1. Permission « créer des droits personnalisés » -> « créer des rôles personnalisés »
DELETE rp FROM `role_permissions` rp
INNER JOIN `permissions` p ON p.id = rp.permission_id
WHERE p.`code` = 'droits.creer';

UPDATE `permissions`
SET `code` = 'roles.creer', `libelle` = 'Créer des rôles personnalisés'
WHERE `code` = 'droits.creer';

INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.`id`, p.`id` FROM `roles` r JOIN `permissions` p
WHERE r.`code` = 'administrateur' AND p.`code` = 'roles.creer';

-- 2. Agences : suppression du champ domaine, renommage de l'initiale
ALTER TABLE `agences` DROP COLUMN `domaine`;
ALTER TABLE `agences` CHANGE COLUMN `domaine_initiale` `agence_initiale` VARCHAR(10) NULL;

-- 3. Utilisateurs : suppression du rattachement domaine (l'agence est la référence)
ALTER TABLE `utilisateurs` DROP FOREIGN KEY `fk_utilisateurs_domaine`;
ALTER TABLE `utilisateurs` DROP INDEX `idx_utilisateurs_domaine`;
ALTER TABLE `utilisateurs` DROP COLUMN `domaine_id`;