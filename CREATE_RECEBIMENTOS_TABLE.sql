-- Tabela para registrar recebimentos das parcelas previstas
CREATE TABLE IF NOT EXISTS `painel_obras_recebimentos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `obra_id` int(10) unsigned NOT NULL,
  `tipo` enum('entrada','parcela') NOT NULL,
  `numero_parcela` int(11) DEFAULT NULL COMMENT 'Número da parcela (NULL para entrada)',
  `valor` decimal(15,2) NOT NULL,
  `data_vencimento` date NOT NULL,
  `data_recebimento` date NOT NULL,
  `observacoes` text,
  `created_by` int(10) unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_obra` (`obra_id`),
  KEY `idx_tipo` (`tipo`),
  CONSTRAINT `fk_recebimentos_obra` FOREIGN KEY (`obra_id`) REFERENCES `painel_obras` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
