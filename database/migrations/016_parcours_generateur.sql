-- =============================================================
-- v0.36 — GÉNÉRATEUR DE PARCOURS par type de permis (directive)
-- Les questions/réponses sont CRÉÉES par l'administrateur.
-- =============================================================

CREATE TABLE `parcours_questions` (
    `id`                   INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id`            INT UNSIGNED NOT NULL,
    `type_permis_id`       INT UNSIGNED NOT NULL,
    `libelle`              VARCHAR(500) NOT NULL,
    `type_reponse`         ENUM('oui_non','choix','nombre','texte') NOT NULL DEFAULT 'oui_non',
    `position`             SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `condition_question_id` INT UNSIGNED NULL,
    `condition_valeur`     VARCHAR(100) NULL,
    `actif`                TINYINT(1)   NOT NULL DEFAULT 1,
    `ajout_le`             DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `supprimer`            TINYINT(1)   NOT NULL DEFAULT 0,
    `supprimer_par`        INT UNSIGNED NULL,
    `supprimer_date`       DATETIME     NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_pq_tenant_type` (`tenant_id`, `type_permis_id`),
    CONSTRAINT `fk_pq_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`),
    CONSTRAINT `fk_pq_type`   FOREIGN KEY (`type_permis_id`) REFERENCES `types_permis` (`id`),
    CONSTRAINT `fk_pq_cond`   FOREIGN KEY (`condition_question_id`) REFERENCES `parcours_questions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `parcours_options` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `question_id` INT UNSIGNED NOT NULL,
    `valeur`     VARCHAR(100) NOT NULL,
    `libelle`    VARCHAR(255) NOT NULL,
    `position`   SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `idx_po_question_valeur` (`question_id`, `valeur`),
    CONSTRAINT `fk_po_question` FOREIGN KEY (`question_id`) REFERENCES `parcours_questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `permissions` (`module`, `code`, `libelle`) VALUES
('types-permis', 'types-permis.parcours', 'Gérer les parcours des types de permis');

INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.`id`, p.`id` FROM `roles` r JOIN `permissions` p
WHERE r.`code` = 'administrateur' AND p.`code` = 'types-permis.parcours';