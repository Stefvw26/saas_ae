-- =============================================================
-- Jalon 7 (suite) — Panier : « CPF = montant à 0 € » configurable
-- (directive utilisateur). Activé par défaut : une ligne CPF compte
-- pour 0 € dans le panier (comme « Offert »), désactivable dans
-- Paramètres > Panier.
-- =============================================================

INSERT INTO `parametres` (`tenant_id`, `cle`, `valeur`, `type`)
SELECT t.`id`, 'cpf_montant_zero', '1', 'booleen' FROM `tenants` t
WHERE NOT EXISTS (
    SELECT 1 FROM `parametres` p WHERE p.`tenant_id` = t.`id` AND p.`cle` = 'cpf_montant_zero'
);
