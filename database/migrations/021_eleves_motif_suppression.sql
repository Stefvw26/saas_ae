-- =============================================================
-- v0.45 — élèves : motif de suppression logique
-- RÈGLE PROJET (directive utilisateur) : JAMAIS de DELETE sur une
-- entité métier — toujours supprimer / supprimer_par / supprimer_date.
-- =============================================================

ALTER TABLE `eleves`
    ADD COLUMN `motif_suppression` VARCHAR(255) NULL AFTER `supprimer_date`;