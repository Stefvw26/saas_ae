-- =============================================================
-- v0.25 — sémantique des options de prestations (directive utilisateur)
-- =============================================================

ALTER TABLE `prestations`
    ADD COLUMN `sejour_type` VARCHAR(40) NULL AFTER `sejour`;