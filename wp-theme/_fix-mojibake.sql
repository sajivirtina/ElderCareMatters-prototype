-- Fix latin1-misread mojibake in seeded ACF values (post_id=2)
-- Run with: mysql --default-character-set=utf8mb4 -u root -proot -P 10053 local < _fix-mojibake.sql
USE local;

UPDATE wp_postmeta SET meta_value = REPLACE(meta_value, 'ÔÇö', '—') WHERE post_id=2 AND meta_value LIKE '%ÔÇö%';
UPDATE wp_postmeta SET meta_value = REPLACE(meta_value, '┬À', '·')  WHERE post_id=2 AND meta_value LIKE '%┬À%';
UPDATE wp_postmeta SET meta_value = REPLACE(meta_value, 'Ôÿà', '★') WHERE post_id=2 AND meta_value LIKE '%Ôÿà%';
UPDATE wp_postmeta SET meta_value = REPLACE(meta_value, '┬®', '©')  WHERE post_id=2 AND meta_value LIKE '%┬®%';
UPDATE wp_postmeta SET meta_value = REPLACE(meta_value, 'ÔåÆ', '→') WHERE post_id=2 AND meta_value LIKE '%ÔåÆ%';
