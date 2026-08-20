-- Adiciona coluna temp_key e torna order_id nullable para suportar gravação
-- durante a criação do pedido (antes do pedido existir no banco)

ALTER TABLE purchase_order_audios
    MODIFY COLUMN order_id INT UNSIGNED DEFAULT NULL,
    ADD COLUMN temp_key VARCHAR(64) DEFAULT NULL AFTER recorded_by_user_id,
    ADD INDEX idx_temp_key (temp_key);
