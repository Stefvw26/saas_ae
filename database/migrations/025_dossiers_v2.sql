-- =============================================================
-- v0.49 — DOSSIERS v2 : états contrat/élève + panier
-- =============================================================

-- Nouveaux états (directive utilisateur) :
-- etat_contrat : a_confirmer (panier non validé) / contrat_genere
-- etat_eleve   : progression de l'élève
ALTER TABLE `dossiers`
    ADD COLUMN `etat_contrat` ENUM('a_confirmer','contrat_genere') NOT NULL DEFAULT 'a_confirmer' AFTER `statut`,
    ADD COLUMN `etat_eleve` ENUM('code_non_commence','code_commence','code_obtenu','pratique_non_commence','pratique_commence','permis_obtenu') NOT NULL DEFAULT 'code_non_commence' AFTER `etat_contrat`,
    ADD COLUMN `type_permis_id` INT UNSIGNED NULL AFTER `agence_id`,
    ADD COLUMN `domaine_id` INT UNSIGNED NULL AFTER `type_permis_id`,
    ADD COLUMN `montant_ttc` DECIMAL(10,2) NULL AFTER `montant_total`;

ALTER TABLE `dossiers`
    ADD INDEX `idx_dossiers_domaine` (`domaine_id`),
    ADD INDEX `idx_dossiers_type_permis` (`type_permis_id`),
    ADD CONSTRAINT `fk_dossiers_domaine` FOREIGN KEY (`domaine_id`) REFERENCES `domaines` (`id`),
    ADD CONSTRAINT `fk_dossiers_type_permis` FOREIGN KEY (`type_permis_id`) REFERENCES `types_permis` (`id`);

-- Panier : prestations d'un dossier (quantité, offert, CPF).
CREATE TABLE `dossier_prestations` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id`     INT UNSIGNED NOT NULL,
    `dossier_id`    INT UNSIGNED NOT NULL,
    `prestation_id` INT UNSIGNED NOT NULL,
    `quantite`      INT UNSIGNED NOT NULL DEFAULT 1,
    `offert`        TINYINT(1)   NOT NULL DEFAULT 0,
    `cpf`           TINYINT(1)   NOT NULL DEFAULT 0,
    `ajout_le`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `ajout_par`     INT UNSIGNED NULL,
    `supprimer`     TINYINT(1)   NOT NULL DEFAULT 0,
    `supprimer_par` INT UNSIGNED NULL,
    `supprimer_date` DATETIME    NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_dp_dossier` (`dossier_id`, `supprimer`),
    INDEX `idx_dp_prestation` (`prestation_id`),
    CONSTRAINT `fk_dp_dossier`    FOREIGN KEY (`dossier_id`)    REFERENCES `dossiers` (`id`),
    CONSTRAINT `fk_dp_prestation` FOREIGN KEY (`prestation_id`) REFERENCES `prestations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;