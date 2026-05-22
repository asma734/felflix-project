-- ============================================================
--  FELFLIX 🌶 — Mise à jour des noms de moods
--  À exécuter dans phpMyAdmin → base omdb_website → onglet SQL
-- ============================================================

UPDATE moods SET name = 'te3eb'      WHERE name = 'te3ben';
UPDATE moods SET name = 'excited'    WHERE name = 'heyej';
UPDATE moods SET name = 'tamou7'     WHERE name IN ('motivé', 'motive');
UPDATE moods SET name = 'met8achech' WHERE name IN ('meta', 'metghachchech');
UPDATE moods SET name = 'neutre'     WHERE name = 'tfakkart';
UPDATE moods SET name = 'roumansi'   WHERE name IN ('romansi', 'taya7');
-- 7zin, far7an, 5ayef restent inchangés

-- Vérification :
SELECT id, name, icon, color FROM moods ORDER BY id;
