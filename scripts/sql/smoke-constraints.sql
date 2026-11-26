-- Teste rápido das regras do banco. Tudo roda numa transação desfeita no final.
-- Cada bloco "deve falhar" é executado com --force e o erro esperado aparece na saída.
START TRANSACTION;
INSERT INTO categories (id) VALUES (900);
INSERT INTO techniques (id) VALUES (900);
INSERT INTO series (id) VALUES (900);
INSERT INTO media_files (id, uuid, kind, original_path, original_filename, mime_type, size_bytes, checksum_sha256)
VALUES (900, 'u900', 'image', 'p900', 'a.jpg', 'image/jpeg', 1, REPEAT('a',64)),
       (901, 'u901', 'image', 'p901', 'b.jpg', 'image/jpeg', 1, REPEAT('b',64));
INSERT INTO artworks (id, year, category_id, technique_id, series_id) VALUES (900, 2024, 900, 900, 900);
INSERT INTO artwork_images (artwork_id, media_file_id, is_primary) VALUES (900, 900, 1);
SELECT 'ok: primeira imagem principal' AS resultado;
-- deve falhar: segunda principal na mesma obra
INSERT INTO artwork_images (artwork_id, media_file_id, is_primary) VALUES (900, 901, 1);
-- deve falhar: obra sem série
INSERT INTO artworks (year, category_id, technique_id) VALUES (2024, 900, 900);
-- deve falhar: preço negativo
INSERT INTO artworks (year, category_id, technique_id, series_id, price_brl) VALUES (2024, 900, 900, 900, -1);
-- deve falhar: WhatsApp fora do formato
INSERT INTO contact_settings (id, recipient_email, whatsapp_number) VALUES (1, 'x@y.z', '+55 11') ON DUPLICATE KEY UPDATE whatsapp_number = '+55 11';
-- deve falhar: slug repetido no mesmo idioma
INSERT INTO artwork_translations (artwork_id, locale_code, title, slug) VALUES (900, 'pt-BR', 'A', 'mesmo');
INSERT INTO artworks (id, year, category_id, technique_id, series_id) VALUES (901, 2024, 900, 900, 900);
INSERT INTO artwork_translations (artwork_id, locale_code, title, slug) VALUES (901, 'pt-BR', 'B', 'mesmo');
-- deve passar: mesmo slug em outro idioma
INSERT INTO artwork_translations (artwork_id, locale_code, title, slug) VALUES (901, 'en', 'B', 'mesmo');
SELECT 'ok: mesmo slug em outro idioma' AS resultado;
ROLLBACK;
