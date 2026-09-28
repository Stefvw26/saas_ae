-- =============================================================
-- v0.21 — icônes types de partenaires, lien prestations↔fournisseur
-- =============================================================

ALTER TABLE `partenaires_type`
    ADD COLUMN `icone` VARCHAR(100) NULL AFTER `descriptif`;

UPDATE `partenaires_type` SET `icone` = 'partenaire-auto-ecole.svg' WHERE `nom` = 'Auto-école' AND `icone` IS NULL;
UPDATE `partenaires_type` SET `icone` = 'partenaire-hotel.svg'      WHERE `nom` = 'Hôtel'      AND `icone` IS NULL;

-- Lien prestation -> partenaire fournisseur (directive : NB de prestations associées)
ALTER TABLE `prestations`
    ADD COLUMN `fournisseur_id` INT UNSIGNED NULL AFTER `fournisseur`;

ALTER TABLE `prestations`
    ADD INDEX `idx_prestations_fournisseur` (`fournisseur_id`),
    ADD CONSTRAINT `fk_prestations_fournisseur` FOREIGN KEY (`fournisseur_id`) REFERENCES `partenaires` (`id`);