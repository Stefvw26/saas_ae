-- =============================================================
-- v0.27 — notifications (J4, CDC §29 en préparation)
-- =============================================================

CREATE TABLE `notifications` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id`      INT UNSIGNED NOT NULL,
    `utilisateur_id` INT UNSIGNED NOT NULL,
    `titre`          VARCHAR(150) NOT NULL,
    `message`        VARCHAR(500) NULL,
    `url`            VARCHAR(255) NULL,
    `lu`             TINYINT(1)   NOT NULL DEFAULT 0,
    `cree_le`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_notifications_utilisateur` (`utilisateur_id`, `lu`),
    INDEX `idx_notifications_tenant` (`tenant_id`),
    CONSTRAINT `fk_notifications_tenant`      FOREIGN KEY (`tenant_id`)      REFERENCES `tenants` (`id`),
    CONSTRAINT `fk_notifications_utilisateur` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;