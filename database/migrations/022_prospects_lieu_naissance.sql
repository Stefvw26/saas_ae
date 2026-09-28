-- =============================================================
-- v0.46 — prospects : lieu de naissance (même ligne que la date)
-- + élèves : type_boite restreint à BA/BM (switches)
-- =============================================================

ALTER TABLE `prospects`
    ADD COLUMN `lieu_naissance` VARCHAR(120) NULL AFTER `date_naissance`;

-- Valeurs historiques PAS/BM/BA nettoyées vers BM/BA (les switches ne
-- proposent que Boîte Manuelle / Boîte Automatique — directive).
UPDATE `eleves` SET `type_boite` = 'BM' WHERE `type_boite` = 'PAS';