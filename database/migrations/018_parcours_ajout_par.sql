-- =============================================================
-- v0.39 — parcours_questions : auteur de création (ajout_par)
-- + reprise du bug v0.37 (auteur écrit dans supprimer_par)
-- =============================================================

ALTER TABLE `parcours_questions`
    ADD COLUMN `ajout_par` INT UNSIGNED NULL AFTER `ajout_le`;

UPDATE `parcours_questions` SET `ajout_par` = `supprimer_par`
 WHERE `supprimer` = 0 AND `supprimer_par` IS NOT NULL AND `ajout_par` IS NULL;

UPDATE `parcours_questions` SET `supprimer_par` = NULL WHERE `supprimer` = 0;

ALTER TABLE `parcours_questions`
    ADD CONSTRAINT `fk_pq_ajout_par` FOREIGN KEY (`ajout_par`) REFERENCES `utilisateurs` (`id`);