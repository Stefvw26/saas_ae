-- =============================================================
-- v0.44 — élèves : lieu de naissance + permission de transfert
-- =============================================================

ALTER TABLE `eleves`
    ADD COLUMN `lieu_naissance` VARCHAR(120) NULL AFTER `date_naissance`;

INSERT INTO `permissions` (`module`, `code`, `libelle`) VALUES
('eleves', 'eleves.agences.transfert', 'Transférer un élève vers une autre agence');

INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.`id`, p.`id` FROM `roles` r JOIN `permissions` p
WHERE r.`code` = 'administrateur' AND p.`code` = 'eleves.agences.transfert';