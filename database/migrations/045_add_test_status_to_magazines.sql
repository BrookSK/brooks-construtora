-- Adiciona o status 'test' ao ENUM da tabela magazines.
-- Usado pelo "Enviar Teste": a revista fica marcada como "Publicada (Teste)"
-- sem ser uma publicação oficial (não vai para o público).

ALTER TABLE `magazines`
    MODIFY COLUMN `status` ENUM('draft', 'generated', 'review', 'approved', 'published', 'test')
    NOT NULL DEFAULT 'draft';
