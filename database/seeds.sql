-- =============================================================================
-- PROJETO INTEGRADOR - SISTEMA AXION (VISTORIA E CHECKLIST VEICULAR)
-- Script de Carga Inicial / Dados de Teste (seeds.sql)
-- =============================================================================

USE `axion_db`;

-- Limpa registros existentes para idempotência das seeds
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE `Ocorrencias`;
TRUNCATE TABLE `EvidenciasVistoria`;
TRUNCATE TABLE `RespostasVistoria`;
TRUNCATE TABLE `Vistorias`;
TRUNCATE TABLE `Subperguntas`;
TRUNCATE TABLE `Perguntas`;
TRUNCATE TABLE `Checklists`;
TRUNCATE TABLE `RecuperacaoSenha`;
TRUNCATE TABLE `Veiculos`;
TRUNCATE TABLE `Usuarios`;
SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================================
-- 1. USUÁRIOS PADRÃO
-- Senha de todos: 'mudar123' (exceto admin: 'admin123')
-- Criptografia gerada com password_hash(..., PASSWORD_DEFAULT)
-- =============================================================================
INSERT INTO `Usuarios` 
(`id_usuario`, `nome`, `email`, `cpf_matricula`, `senha`, `perfil`, `telefone`, `cidade`, `estado`, `ativo`) 
VALUES
(1, 'Administrador Geral', 'admin@axion.com', '000.000.000-00', '$2y$10$KUv3GX3h6VZVbOrPyRly1.jtNSwNx/2Poqe0.b42YwgEFa2t0k2z2', 'gestor', '(27) 99999-0000', 'Vitória', 'ES', 1),
(2, 'Maria Oliveira', 'supervisor@axion.com', '111.111.111-11', '$2y$10$mL6vlWGpIDyoul.L3SqQFuAdm4h8WT6Xkgv0Fa79agWAfGdkKDxTq', 'supervisor', '(27) 99999-1111', 'Vila Velha', 'ES', 1),
(3, 'João Silva', 'motorista@axion.com', '222.222.222-22', '$2y$10$mL6vlWGpIDyoul.L3SqQFuAdm4h8WT6Xkgv0Fa79agWAfGdkKDxTq', 'motorista', '(27) 99999-2222', 'Serra', 'ES', 1),
(4, 'Carlos Mendes (Cliente)', 'cliente@axion.com', '333.333.333-33', '$2y$10$mL6vlWGpIDyoul.L3SqQFuAdm4h8WT6Xkgv0Fa79agWAfGdkKDxTq', 'cliente', '(27) 99999-3333', 'Cariacica', 'ES', 1);

-- =============================================================================
-- 2. VEÍCULOS DE TESTE
-- =============================================================================
INSERT INTO `Veiculos`
(`id_veiculo`, `placa`, `marca`, `modelo`, `marca_modelo`, `ano`, `cor`, `km_rodado`, `renavam`, `chassi`, `status`)
VALUES
(1, 'ABC1D23', 'Volkswagen', 'Gol 1.0', 'Volkswagen Gol 1.0', 2022, 'Prata', 45000, '12345678901', '9BWZZZ377VT000000', 'ativo'),
(2, 'XYZ9876', 'Fiat', 'Uno Mille', 'Fiat Uno Mille', 2020, 'Branco', 62000, '98765432100', '9BD158223B0000000', 'ativo');

-- =============================================================================
-- 3. MODELO DE CHECKLIST EXEMPLO
-- =============================================================================
INSERT INTO `Checklists`
(`id_checklist`, `id_criador`, `titulo`, `categoria`, `descricao`, `status`)
VALUES
(1, 1, 'Vistoria Diária de Segurança', 'Segurança Geral', 'Checklist padrão diário pré-operação da frota', 'ativo');

-- =============================================================================
-- 4. PERGUNTAS DO CHECKLIST
-- =============================================================================
INSERT INTO `Perguntas`
(`id_pergunta`, `id_checklist`, `texto_pergunta`, `tipo_resposta`, `obrigatorio`, `categoria`, `ordem`)
VALUES
(1, 1, 'O veículo possui alguma avaria visível na lataria ou pintura?', 'sim_nao', 1, 'estetica', 1),
(2, 1, 'Os faróis, lanternas e setas estão operando corretamente?', 'sim_nao', 1, 'eletrica', 2),
(3, 1, 'Nível do óleo e fluido de arrefecimento estão dentro do padrão?', 'sim_nao', 1, 'mecanica', 3),
(4, 1, 'Os cintos de segurança e itens obrigatórios (estepe, macaco) estão disponíveis?', 'sim_nao', 1, 'interior', 4),
(5, 1, 'Informe o valor atual do hodômetro:', 'numero', 1, 'mecanica', 5);

-- =============================================================================
-- 5. SUBPERGUNTAS CONDICIONAIS
-- Disparadas caso a resposta atenda ao gatilho da pergunta pai
-- =============================================================================
INSERT INTO `Subperguntas`
(`id_subpergunta`, `id_pergunta_pai`, `condicao_resposta`, `texto_pergunta`, `tipo_resposta`, `obrigatorio`, `ordem`)
VALUES
(1, 1, 'Sim', 'Especifique o tipo de avaria e a localização exata na lataria:', 'texto', 1, 1);

