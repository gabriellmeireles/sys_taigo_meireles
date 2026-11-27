-- Uso único no banco local: traduções revisadas antes da etapa 4 (sem source_hash na
-- revisão) que foram marcadas como desatualizadas pela primeira sincronização.
UPDATE artwork_translations t
JOIN artwork_translations s ON s.artwork_id = t.artwork_id AND s.locale_code = 'pt-BR'
SET t.is_outdated = 0, t.source_hash = s.source_hash
WHERE t.translation_status = 'manual' AND t.locale_code <> 'pt-BR' AND t.is_outdated = 1 AND t.reviewed_at IS NULL;
SELECT artwork_id, locale_code, title, translation_status, is_outdated FROM artwork_translations WHERE translation_status = 'manual' AND locale_code <> 'pt-BR';
