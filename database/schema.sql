-- =============================================================================
-- PROJETO INTEGRADOR - SISTEMA AXION (VISTORIA E CHECKLIST VEICULAR)
-- Script DDL de Estrutura do Banco de Dados (schema.sql)
-- Compatível com MySQL 5.7+ / 8.0+ e MariaDB (Padrão XAMPP)
-- =============================================================================

CREATE DATABASE IF NOT EXISTS `axion_db` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `axion_db`;

-- Desabilita temporariamente verificações de chaves estrangeiras para limpeza ordenada
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `Ocorrencias`;
DROP TABLE IF EXISTS `EvidenciasVistoria`;
DROP TABLE IF EXISTS `RespostasVistoria`;
DROP TABLE IF EXISTS `Vistorias`;
DROP TABLE IF EXISTS `Subperguntas`;
DROP TABLE IF EXISTS `Perguntas`;
DROP TABLE IF EXISTS `Checklists`;
DROP TABLE IF EXISTS `RecuperacaoSenha`;
DROP TABLE IF EXISTS `Veiculos`;
DROP TABLE IF EXISTS `Usuarios`;

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================================
-- 1. TABELA: Usuarios
-- Centraliza os acessos de Administradores, Supervisores, Motoristas e Clientes
-- =============================================================================
CREATE TABLE `Usuarios` (
    `id_usuario` INT AUTO_INCREMENT PRIMARY KEY,
    `nome` VARCHAR(150) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `cpf_matricula` VARCHAR(20) NOT NULL UNIQUE,
    `senha` VARCHAR(255) NOT NULL,
    `perfil` ENUM('gestor', 'supervisor', 'motorista', 'cliente') NOT NULL DEFAULT 'motorista',
    `data_nascimento` DATE NULL,
    `genero` VARCHAR(20) NULL,
    `cnh` VARCHAR(20) NULL,
    `validade_cnh` DATE NULL,
    `categoria_cnh` VARCHAR(5) NULL,
    `logradouro` VARCHAR(200) NULL,
    `numero` VARCHAR(20) NULL,
    `bairro` VARCHAR(100) NULL,
    `cidade` VARCHAR(100) NULL,
    `estado` VARCHAR(2) NULL,
    `cep` VARCHAR(10) NULL,
    `telefone` VARCHAR(20) NULL,
    `ativo` TINYINT(1) NOT NULL DEFAULT 1,
    `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_usuarios_email` (`email`),
    INDEX `idx_usuarios_cpf` (`cpf_matricula`),
    INDEX `idx_usuarios_perfil` (`perfil`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 2. TABELA: Veiculos
-- Frota de veículos cadastrados para inspeção
-- =============================================================================
CREATE TABLE `Veiculos` (
    `id_veiculo` INT AUTO_INCREMENT PRIMARY KEY,
    `placa` VARCHAR(10) NOT NULL UNIQUE,
    `marca` VARCHAR(50) NULL,
    `modelo` VARCHAR(50) NULL,
    `marca_modelo` VARCHAR(100) NOT NULL,
    `ano` INT NULL,
    `cor` VARCHAR(30) NULL,
    `km_rodado` INT DEFAULT 0,
    `renavam` VARCHAR(20) NULL,
    `chassi` VARCHAR(30) NULL,
    `status` ENUM('ativo', 'manutencao', 'inativo') NOT NULL DEFAULT 'ativo',
    `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_veiculos_placa` (`placa`),
    INDEX `idx_veiculos_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 3. TABELA: Checklists (Modelos de Vistoria)
-- Modelos e formulários criados dinamicamente pelos gestores/supervisores
-- =============================================================================
CREATE TABLE `Checklists` (
    `id_checklist` INT AUTO_INCREMENT PRIMARY KEY,
    `id_criador` INT NOT NULL,
    `titulo` VARCHAR(200) NOT NULL,
    `categoria` VARCHAR(50) NULL,
    `descricao` TEXT NULL,
    `aprovado_por` INT NULL COMMENT 'ID do Gestor que validou o modelo',
    `motivo_ajuste` TEXT NULL COMMENT 'Parecer do Gestor caso devolva para correções',
    `data_aprovacao` DATETIME NULL,
    `status` ENUM('ativo', 'pendente_aprovacao', 'ajuste_solicitado', 'inativo') NOT NULL DEFAULT 'ativo',
    `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_checklists_status` (`status`),
    CONSTRAINT `fk_checklists_criador` 
        FOREIGN KEY (`id_criador`) REFERENCES `Usuarios` (`id_usuario`) 
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_checklists_aprovador` 
        FOREIGN KEY (`aprovado_por`) REFERENCES `Usuarios` (`id_usuario`) 
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 4. TABELA: Perguntas
-- Itens / perguntas principais que compõem cada modelo de checklist
-- =============================================================================
CREATE TABLE `Perguntas` (
    `id_pergunta` INT AUTO_INCREMENT PRIMARY KEY,
    `id_checklist` INT NOT NULL,
    `texto_pergunta` TEXT NOT NULL,
    `tipo_resposta` ENUM('sim_nao', 'texto', 'numero') NOT NULL DEFAULT 'sim_nao',
    `obrigatorio` TINYINT(1) NOT NULL DEFAULT 1,
    `categoria` VARCHAR(50) NOT NULL,
    `ordem` INT NOT NULL DEFAULT 1,
    `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_perguntas_checklist` (`id_checklist`),
    CONSTRAINT `fk_perguntas_checklist` 
        FOREIGN KEY (`id_checklist`) REFERENCES `Checklists` (`id_checklist`) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 5. TABELA: Subperguntas (Perguntas Condicionais)
-- Perguntas disparadas caso a resposta da pergunta pai atenda à condição
-- =============================================================================
CREATE TABLE `Subperguntas` (
    `id_subpergunta` INT AUTO_INCREMENT PRIMARY KEY,
    `id_pergunta_pai` INT NOT NULL,
    `condicao_resposta` VARCHAR(50) NOT NULL COMMENT 'Condição que aciona a subpergunta (ex: Sim, Nao)',
    `texto_pergunta` TEXT NOT NULL,
    `tipo_resposta` ENUM('sim_nao', 'texto', 'numero') NOT NULL DEFAULT 'texto',
    `obrigatorio` TINYINT(1) NOT NULL DEFAULT 1,
    `ordem` INT NOT NULL DEFAULT 1,
    `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_subperguntas_pai` (`id_pergunta_pai`),
    CONSTRAINT `fk_subperguntas_pergunta` 
        FOREIGN KEY (`id_pergunta_pai`) REFERENCES `Perguntas` (`id_pergunta`) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 6. TABELA: Vistorias
-- Registro de cada execução de vistoria veicular preenchida
-- =============================================================================
CREATE TABLE `Vistorias` (
    `id_vistoria` INT AUTO_INCREMENT PRIMARY KEY,
    `id_checklist` INT NOT NULL,
    `id_veiculo` INT NULL,
    `placa_veiculo` VARCHAR(10) NOT NULL,
    `nome_motorista` VARCHAR(150) NOT NULL,
    `id_motorista` INT NULL,
    `data_vistoria` DATE NOT NULL,
    `hora_vistoria` TIME NOT NULL,
    `km_rodado` INT NOT NULL DEFAULT 0,
    `nome_vistoriador` VARCHAR(150) NOT NULL,
    `id_vistoriador` INT NULL,
    `descricao_nao_conformidade` TEXT NULL,
    `assinatura_motorista` LONGTEXT NULL COMMENT 'Assinatura digital Base64 (canvas)',
    `assinatura_vistoriador` LONGTEXT NULL COMMENT 'Assinatura digital Base64 (canvas)',
    `status` ENUM('aprovado', 'aprovado_com_restricoes', 'rejeitado', 'pendente') NOT NULL DEFAULT 'pendente',
    `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_vistorias_placa` (`placa_veiculo`),
    INDEX `idx_vistorias_data` (`data_vistoria`),
    CONSTRAINT `fk_vistorias_checklist` 
        FOREIGN KEY (`id_checklist`) REFERENCES `Checklists` (`id_checklist`) 
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_vistorias_veiculo` 
        FOREIGN KEY (`id_veiculo`) REFERENCES `Veiculos` (`id_veiculo`) 
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_vistorias_motorista` 
        FOREIGN KEY (`id_motorista`) REFERENCES `Usuarios` (`id_usuario`) 
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_vistorias_vistoriador` 
        FOREIGN KEY (`id_vistoriador`) REFERENCES `Usuarios` (`id_usuario`) 
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 7. TABELA: RespostasVistoria
-- Respostas individuais para cada pergunta e subpergunta de uma vistoria
-- =============================================================================
CREATE TABLE `RespostasVistoria` (
    `id_resposta` INT AUTO_INCREMENT PRIMARY KEY,
    `id_vistoria` INT NOT NULL,
    `id_pergunta` INT NOT NULL,
    `id_subpergunta` INT NULL,
    `valor_resposta` TEXT NOT NULL,
    `conforme` TINYINT(1) NULL COMMENT '1=Conforme, 0=Nao conforme, NULL=informativo',
    `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_respostas_vistoria` (`id_vistoria`),
    CONSTRAINT `fk_respostas_vistoria` 
        FOREIGN KEY (`id_vistoria`) REFERENCES `Vistorias` (`id_vistoria`) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_respostas_pergunta` 
        FOREIGN KEY (`id_pergunta`) REFERENCES `Perguntas` (`id_pergunta`) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_respostas_subpergunta` 
        FOREIGN KEY (`id_subpergunta`) REFERENCES `Subperguntas` (`id_subpergunta`) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 8. TABELA: EvidenciasVistoria
-- Fotos e documentos anexados em situações de não conformidade
-- =============================================================================
CREATE TABLE `EvidenciasVistoria` (
    `id_evidencia` INT AUTO_INCREMENT PRIMARY KEY,
    `id_vistoria` INT NOT NULL,
    `tipo_evidencia` ENUM('foto', 'documento') NOT NULL DEFAULT 'foto',
    `caminho_arquivo` VARCHAR(255) NOT NULL,
    `nome_original` VARCHAR(255) NOT NULL,
    `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_evidencias_vistoria` (`id_vistoria`),
    CONSTRAINT `fk_evidencias_vistoria` 
        FOREIGN KEY (`id_vistoria`) REFERENCES `Vistorias` (`id_vistoria`) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 9. TABELA: RecuperacaoSenha
-- Tokens de autenticação temporários para redefinição de senhas
-- =============================================================================
CREATE TABLE `RecuperacaoSenha` (
    `id_recuperacao` INT AUTO_INCREMENT PRIMARY KEY,
    `id_usuario` INT NOT NULL,
    `token` VARCHAR(64) NOT NULL UNIQUE,
    `expira_em` DATETIME NOT NULL,
    `usado` TINYINT(1) NOT NULL DEFAULT 0,
    `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_recuperacao_token` (`token`),
    CONSTRAINT `fk_recuperacao_usuario` 
        FOREIGN KEY (`id_usuario`) REFERENCES `Usuarios` (`id_usuario`) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 10. TABELA: Ocorrencias
-- Gestão e triagem de não conformidades operacionais e despacho para oficina
-- =============================================================================
CREATE TABLE `Ocorrencias` (
    `id_ocorrencia` INT AUTO_INCREMENT PRIMARY KEY,
    `codigo_ocorrencia` VARCHAR(30) NOT NULL UNIQUE,
    `id_vistoria` INT NOT NULL,
    `id_veiculo` INT NULL,
    `placa_veiculo` VARCHAR(10) NOT NULL,
    `modelo_veiculo` VARCHAR(100) NOT NULL,
    `subsistema` VARCHAR(50) NOT NULL DEFAULT 'Geral',
    `descricao_falha` TEXT NOT NULL,
    `criticidade` ENUM('baixa', 'media', 'alta', 'critica') NOT NULL DEFAULT 'media',
    `status` ENUM('aberta', 'em_oficina', 'resolvida') NOT NULL DEFAULT 'aberta',
    `status_veiculo` ENUM('retido_oficina', 'liberado') NOT NULL DEFAULT 'retido_oficina',
    `acao_recomendada` TEXT NULL,
    `foto_evidencia` VARCHAR(255) NULL,
    `local_patio` VARCHAR(100) DEFAULT 'Pátio Principal',
    `fiscal_responsavel` VARCHAR(150) NOT NULL,
    `data_abertura` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `data_resolucao` DATETIME NULL,
    `observacao_despacho` TEXT NULL,
    `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_ocorrencias_status` (`status`),
    INDEX `idx_ocorrencias_criticidade` (`criticidade`),
    INDEX `idx_ocorrencias_placa` (`placa_veiculo`),
    CONSTRAINT `fk_ocorrencias_vistoria` 
        FOREIGN KEY (`id_vistoria`) REFERENCES `Vistorias` (`id_vistoria`) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_ocorrencias_veiculo` 
        FOREIGN KEY (`id_veiculo`) REFERENCES `Veiculos` (`id_veiculo`) 
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

