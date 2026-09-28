-- v0.37 — types de permis : photographie (avec prévisualisation)
ALTER TABLE `types_permis`
    ADD COLUMN `photographie` VARCHAR(255) NULL AFTER `descriptif`;