-- =============================================================
-- v0.50 — Panier : activation Offert / CPF via les paramètres
-- (directive utilisateur). Activés par défaut (comportement inchangé).
-- =============================================================

INSERT INTO `parametres` (`tenant_id`, `cle`, `valeur`, `type`)
SELECT t.`id`, 'activer_panier_offert', '1', 'booleen' FROM `tenants` t
WHERE NOT EXISTS (
    SELECT 1 FROM `parametres` p WHERE p.`tenant_id` = t.`id` AND p.`cle` = 'activer_panier_offert'
);

INSERT INTO `parametres` (`tenant_id`, `cle`, `valeur`, `type`)
SELECT t.`id`, 'activer_panier_cpf', '1', 'booleen' FROM `tenants` t
WHERE NOT EXISTS (
    SELECT 1 FROM `parametres` p WHERE p.`tenant_id` = t.`id` AND p.`cle` = 'activer_panier_cpf'
);