-- =============================================================
-- v0.31 — agences : adresse complète + email (directives utilisateur)
-- =============================================================

ALTER TABLE `agences`
    ADD COLUMN `code_postal` VARCHAR(10)  NULL AFTER `agence_adresse`,
    ADD COLUMN `ville`       VARCHAR(80)  NULL AFTER `code_postal`,
    ADD COLUMN `pays`        VARCHAR(80)  NULL AFTER `ville`,
    ADD COLUMN `agence_mail` VARCHAR(190) NULL AFTER `agence_telephone`;