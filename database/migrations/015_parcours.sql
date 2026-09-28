-- =============================================================
-- v0.35 — parcours prospects (CDC §41 : règles VALIDÉES utilisateur)
-- Types de permis : nature du parcours (conduite / code / aucun)
-- =============================================================

ALTER TABLE `types_permis`
    ADD COLUMN `parcours_type` ENUM('conduite','code','aucun') NOT NULL DEFAULT 'aucun' AFTER `initiale`;