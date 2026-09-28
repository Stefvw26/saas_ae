-- =============================================================
-- Jalon 7 (suite) — Fiche dossier : fondations
-- documents (polymorphe), dossier_declarations (cerfa/ediser/neph),
-- dossier_echeances, remise (colonnes dossiers), checklist des
-- documents obligatoires par dossier, motif_suppression (mirroir
-- eleves.motif_suppression — RÈGLE PROJET : jamais de DELETE réel).
-- =============================================================

/* --- État du dossier : ajout de l'étape intermédiaire "panier validé" --- */
ALTER TABLE `dossiers`
    MODIFY COLUMN `etat_contrat` ENUM('a_confirmer','panier_valide','contrat_genere') NOT NULL DEFAULT 'a_confirmer';

/* --- Distinction Archiver / Supprimer (les deux restent une suppression logique) --- */
ALTER TABLE `dossiers`
    ADD COLUMN `motif_suppression` VARCHAR(255) NULL AFTER `supprimer_date`;

/* --- Mode de sélection (à la carte / formule) : nécessaire pour la card récap
   ("nom de la formule" ou "Prestation à la carte (x prestations)") — jusqu'ici
   ce mode n'était qu'un état transitoire côté navigateur, jamais enregistré. --- */
ALTER TABLE `dossiers`
    ADD COLUMN `mode`       ENUM('a_la_carte','formule') NOT NULL DEFAULT 'a_la_carte' AFTER `type_permis_id`,
    ADD COLUMN `formule_id` INT UNSIGNED NULL AFTER `mode`,
    ADD CONSTRAINT `fk_dossiers_formule` FOREIGN KEY (`formule_id`) REFERENCES `formules` (`id`);

/* --- Remise (un seul justificatif par dossier, montant fixe uniquement) --- */
ALTER TABLE `dossiers`
    ADD COLUMN `remise_justificatif` VARCHAR(50)  NULL AFTER `montant_ttc`,
    ADD COLUMN `remise_montant`      DECIMAL(10,2) NULL AFTER `remise_justificatif`,
    ADD COLUMN `remise_commentaire`  VARCHAR(500)  NULL AFTER `remise_montant`,
    ADD COLUMN `remise_ajout_par`    INT UNSIGNED  NULL AFTER `remise_commentaire`,
    ADD COLUMN `remise_ajout_le`     DATETIME      NULL AFTER `remise_ajout_par`;

/* --- Documents (générique, polymorphe objet_type/objet_id — même principe que commentaires) --- */
CREATE TABLE `documents` (
    `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id`      INT UNSIGNED NOT NULL,
    `objet_type`     VARCHAR(50)  NOT NULL,
    `objet_id`       INT UNSIGNED NOT NULL,
    `type`           VARCHAR(30)  NOT NULL,           -- devis, contrat, contrat_signe, cerfa, libre
    `libelle`        VARCHAR(150) NOT NULL,           -- affiché à l'écran (auto pour les types système, libre sinon)
    `nom_original`   VARCHAR(255) NOT NULL,
    `chemin`         VARCHAR(500) NOT NULL,
    `mime`           VARCHAR(100) NOT NULL,
    `taille`         INT UNSIGNED NOT NULL,
    `ajout_le`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `ajout_par`      INT UNSIGNED NULL,
    `supprimer`      TINYINT(1)   NOT NULL DEFAULT 0,
    `supprimer_par`  INT UNSIGNED NULL,
    `supprimer_date` DATETIME     NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_documents_objet` (`objet_type`, `objet_id`, `supprimer`),
    INDEX `idx_documents_tenant` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* --- Déclarations CERFA / EDISER / NEPH (une ligne par type et par dossier) --- */
CREATE TABLE `dossier_declarations` (
    `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id`      INT UNSIGNED NOT NULL,
    `dossier_id`     INT UNSIGNED NOT NULL,
    `type`           ENUM('cerfa','ediser','neph') NOT NULL,
    `numero`         VARCHAR(100) NULL,               -- N° CERFA / code élève Ediser / N° NEPH
    `date_envoi`     DATE NULL,                       -- CERFA : date d'envoi du formulaire
    `date_reception` DATE NULL,                       -- CERFA : date de réception de la validation
    `commentaire`    VARCHAR(500) NULL,
    `document_id`    INT UNSIGNED NULL,               -- CERFA : fichier joint
    `ajout_le`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `ajout_par`      INT UNSIGNED NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_declaration_dossier_type` (`dossier_id`, `type`),
    INDEX `idx_declarations_tenant` (`tenant_id`),
    CONSTRAINT `fk_declarations_dossier`  FOREIGN KEY (`dossier_id`)  REFERENCES `dossiers` (`id`),
    CONSTRAINT `fk_declarations_document` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* --- Échéances de paiement --- */
CREATE TABLE `dossier_echeances` (
    `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id`        INT UNSIGNED NOT NULL,
    `dossier_id`       INT UNSIGNED NOT NULL,
    `numero_echeance`  TINYINT UNSIGNED NOT NULL,
    `date_echeance`    DATE NOT NULL,
    `mode_paiement_id` INT UNSIGNED NULL,
    `montant_ttc`      DECIMAL(10,2) NOT NULL,
    `date_paiement`    DATE NULL,
    `etat`             ENUM('a_venir','payee','en_retard','annulee') NOT NULL DEFAULT 'a_venir',
    `commentaire`      VARCHAR(500) NULL,
    `ajout_le`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `ajout_par`        INT UNSIGNED NULL,
    `supprimer`        TINYINT(1) NOT NULL DEFAULT 0,
    `supprimer_par`    INT UNSIGNED NULL,
    `supprimer_date`   DATETIME NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_echeances_dossier` (`dossier_id`, `supprimer`),
    CONSTRAINT `fk_echeances_dossier` FOREIGN KEY (`dossier_id`) REFERENCES `dossiers` (`id`),
    CONSTRAINT `fk_echeances_mode`    FOREIGN KEY (`mode_paiement_id`) REFERENCES `modes_paiement` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* --- Checklist des documents obligatoires par dossier --- */
CREATE TABLE `dossier_documents_obligatoires` (
    `id`                        INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id`                 INT UNSIGNED NOT NULL,
    `dossier_id`                INT UNSIGNED NOT NULL,
    `document_obligatoire_id`   INT UNSIGNED NOT NULL,
    `recu`                      TINYINT(1) NOT NULL DEFAULT 0,
    `document_id`               INT UNSIGNED NULL,
    `ajout_le`                  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `ajout_par`                 INT UNSIGNED NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_ddo_dossier_doc` (`dossier_id`, `document_obligatoire_id`),
    CONSTRAINT `fk_ddo_dossier`  FOREIGN KEY (`dossier_id`) REFERENCES `dossiers` (`id`),
    CONSTRAINT `fk_ddo_document_obligatoire` FOREIGN KEY (`document_obligatoire_id`) REFERENCES `documents_obligatoires` (`id`),
    CONSTRAINT `fk_ddo_document` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
