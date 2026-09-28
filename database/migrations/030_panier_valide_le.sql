-- =============================================================
-- Jalon 7 (suite) — date de validation du panier, nécessaire pour
-- calculer les dates d'échéances (base + 1 mois par échéance, directive).
-- =============================================================

ALTER TABLE `dossiers`
    ADD COLUMN `panier_valide_le` DATETIME NULL AFTER `etat_contrat`;
