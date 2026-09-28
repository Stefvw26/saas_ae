-- =============================================================
-- JALON 7 — Référentiels complémentaires (préparation fiche dossier)
-- documents_obligatoires : pièces que l'élève doit remettre (photo, CIN, …)
-- modes_paiement         : modes de règlement des échéances
-- domaines.nb_echeances_max : nombre d'échéances max, paramétrable par
-- domaine (remplace la règle « 3 Province / 4 Paris » en dur — directive).
-- =============================================================

CREATE TABLE `documents_obligatoires` (
    `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id`      INT UNSIGNED NOT NULL,
    `nom`            VARCHAR(100) NOT NULL,
    `descriptif`     VARCHAR(255) NULL,
    `initiale`       VARCHAR(10)  NULL,
    `actif`          TINYINT(1)   NOT NULL DEFAULT 1,
    `ajout_le`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `ajout_par`      INT UNSIGNED NULL,
    `supprimer`      TINYINT(1)   NOT NULL DEFAULT 0,
    `supprimer_par`  INT UNSIGNED NULL,
    `supprimer_date` DATETIME     NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_documents_obligatoires_tenant` (`tenant_id`),
    CONSTRAINT `fk_documents_obligatoires_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `modes_paiement` (
    `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id`      INT UNSIGNED NOT NULL,
    `nom`            VARCHAR(100) NOT NULL,
    `actif`          TINYINT(1)   NOT NULL DEFAULT 1,
    `ajout_le`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `ajout_par`      INT UNSIGNED NULL,
    `supprimer`      TINYINT(1)   NOT NULL DEFAULT 0,
    `supprimer_par`  INT UNSIGNED NULL,
    `supprimer_date` DATETIME     NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_modes_paiement_tenant` (`tenant_id`),
    CONSTRAINT `fk_modes_paiement_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `domaines`
    ADD COLUMN `nb_echeances_max` TINYINT UNSIGNED NOT NULL DEFAULT 3 AFTER `couleur`;

INSERT INTO `permissions` (`module`, `code`, `libelle`) VALUES
('documents-obligatoires', 'documents-obligatoires.consulter', 'Consulter les documents obligatoires'),
('documents-obligatoires', 'documents-obligatoires.creer',     'Créer un document obligatoire'),
('documents-obligatoires', 'documents-obligatoires.modifier',  'Modifier un document obligatoire'),
('documents-obligatoires', 'documents-obligatoires.supprimer', 'Supprimer (archiver) un document obligatoire'),
('modes-paiement',         'modes-paiement.consulter',         'Consulter les modes de paiement'),
('modes-paiement',         'modes-paiement.creer',             'Créer un mode de paiement'),
('modes-paiement',         'modes-paiement.modifier',          'Modifier un mode de paiement'),
('modes-paiement',         'modes-paiement.supprimer',         'Supprimer (archiver) un mode de paiement');

INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.`id`, p.`id` FROM `roles` r JOIN `permissions` p
WHERE r.`code` = 'administrateur' AND p.`module` IN ('documents-obligatoires', 'modes-paiement');

INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.`id`, p.`id` FROM `roles` r JOIN `permissions` p
WHERE r.`code` = 'directeur' AND p.`code` IN (
    'documents-obligatoires.consulter', 'modes-paiement.consulter'
);

/* Valeurs initiales des modes de paiement (liste fournie), une série par tenant existant. */
INSERT INTO `modes_paiement` (`tenant_id`, `nom`, `ajout_le`)
SELECT t.`id`, v.`nom`, CURRENT_TIMESTAMP
FROM `tenants` t
CROSS JOIN (
    SELECT 'Non défini' AS nom
    UNION ALL SELECT 'Espèces'
    UNION ALL SELECT 'Carte Bancaire (Sur Place)'
    UNION ALL SELECT 'Carte Bancaire (VAD Non remis)'
    UNION ALL SELECT 'Carte Bancaire (VAD remis)'
    UNION ALL SELECT 'Chèque non remis'
    UNION ALL SELECT 'Chèque remis en agence'
    UNION ALL SELECT 'Virement'
) v;
