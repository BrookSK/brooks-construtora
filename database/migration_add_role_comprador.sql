-- =====================================================
-- Adicionar role 'comprador' na tabela users
-- =====================================================
-- Se a coluna role for ENUM, precisa alterar para incluir o novo valor:
ALTER TABLE users MODIFY COLUMN role VARCHAR(20) DEFAULT 'editor';

-- Caso a coluna role já seja VARCHAR, este ALTER é apenas para garantir
-- que tem espaço suficiente para o valor 'comprador'.
-- 
-- Roles disponíveis agora:
--   super_admin - Acesso total ao sistema
--   admin       - Dashboard, config, newsletter, usuários, revistas, pedidos completo
--   comprador   - Dashboard, pedidos (criar/gerenciar), fornecedores, materiais
--   designer    - Dashboard, revistas (editar)
--   editor      - Dashboard, visualizar revistas, visualizar pedidos
