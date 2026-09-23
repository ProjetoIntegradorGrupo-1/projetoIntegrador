-- =========================================================
-- SCRIPT DE DADOS SIMULADOS (MOCK DATA) - AXION PRO
-- Baseado na estrutura oficial: inspecao_veicular
-- =========================================================

USE inspecao_veicular;

-- Desativar temporariamente verificação de chaves estrangeiras para inserção limpa
SET FOREIGN_KEY_CHECKS = 0;

-- Limpar tabelas existentes (se houver testes anteriores)
TRUNCATE TABLE Ocorrencias;
TRUNCATE TABLE Evidencias;
TRUNCATE TABLE Respostas;
TRUNCATE TABLE Inspecoes;
TRUNCATE TABLE Perguntas;
TRUNCATE TABLE Checklists;
TRUNCATE TABLE Veiculos;
TRUNCATE TABLE CNHs;
TRUNCATE TABLE Enderecos;
TRUNCATE TABLE Usuarios;

SET FOREIGN_KEY_CHECKS = 1;


-- =========================================================
-- 1. USUÁRIOS
-- =========================================================
INSERT INTO Usuarios (id_usuario, nome, cpf, data_nascimento, telefone, email, senha, perfil, status) VALUES 
(1, 'Carlos Eduardo Silva', '12345678901', '1985-06-12', '(27) 99888-7766', 'carlos.gestor@axion.com', '$2y$10$exemploHashSenhaGestor123', 'gestor', 'ativo'),
(2, 'Ana Beatriz Souza', '98765432199', '1992-11-04', '(27) 99777-6655', 'ana.motorista@axion.com', '$2y$10$exemploHashSenhaMotorista123', 'funcionario', 'ativo');


-- =========================================================
-- 2. ENDEREÇOS
-- =========================================================
INSERT INTO Enderecos (id_endereco, id_usuario, logradouro, numero, bairro, cidade, estado, cep) VALUES 
(1, 1, 'Av. Central', '450', 'Laranjeiras', 'Serra', 'ES', '29160-000'),
(2, 2, 'Rua das Flores', '12', 'Parque Residencial Laranjeiras', 'Serra', 'ES', '29165-440');


-- =========================================================
-- 3. CNHs
-- =========================================================
INSERT INTO CNHs (id_cnh, id_usuario, numero, categoria, validade) VALUES 
(1, 1, '12345678900', 'AB', '2028-05-20'),
(2, 2, '98765432100', 'B', '2029-08-15');


-- =========================================================
-- 4. VEÍCULOS
-- =========================================================
INSERT INTO Veiculos (id_veiculo, placa, marca, modelo, ano, cor, quilometragem, renavam, chassi, status) VALUES 
(1, 'ABC1D23', 'Fiat', 'Mobi Like', 2023, 'Branco', 14250, '12345678901', '9BWZZZ377VT000001', 'ativo'),
(2, 'XYZ9K88', 'Renault', 'Kangoo Express', 2022, 'Prata', 38900, '23456789012', '93YF12345VT000002', 'ativo'),
(3, 'BRA2E10', 'Chevrolet', 'Spin LT', 2024, 'Cinza', 8100, '34567890123', '9BG123456VT000003', 'ativo');


-- =========================================================
-- 5. CHECKLISTS
-- =========================================================
INSERT INTO Checklists (id_checklist, id_criador, titulo, categoria, status) VALUES 
(1, 1, 'Inspeção Diária de Saída (Veículos Leves)', 'Geral', 'ativo'),
(2, 1, 'Verificação de Manutenção Periódica', 'Mecânica', 'ativo');


-- =========================================================
-- 6. PERGUNTAS
-- =========================================================
INSERT INTO Perguntas (id_pergunta, id_checklist, texto_pergunta, tipo_resposta, obrigatorio, resposta_esperada, orientacao_fora_padrao, ordem, id_pergunta_pai, condicao_resposta) VALUES 
(1, 1, 'Os pneus estão calibrados e em bom estado?', 'sim_nao', TRUE, 'sim', 'Calibrar ou substituir pneu danificado antes de rodar.', 1, NULL, NULL),
(2, 1, 'Nível de óleo do motor está adequado?', 'sim_nao', TRUE, 'sim', 'Completar o nível de óleo antes da viagem.', 2, NULL, NULL),
(3, 1, 'Quilometragem atual do veículo:', 'numero', TRUE, NULL, NULL, 3, NULL, NULL),
(4, 1, 'Observações gerais sobre a lataria:', 'texto', FALSE, NULL, NULL, 4, NULL, NULL);


-- =========================================================
-- 7. INSPEÇÕES
-- =========================================================
INSERT INTO Inspecoes (id_inspecao, id_checklist, id_veiculo, id_usuario, quilometragem, status, data_inicio, data_finalizacao, observacao_geral) VALUES 
(1, 1, 1, 2, 14250, 'finalizada', '2026-06-01 08:30:00', '2026-06-01 08:45:00', 'Veículo em perfeito estado para uso institucional.');


-- =========================================================
-- 8. RESPOSTAS
-- =========================================================
INSERT INTO Respostas (id_resposta, id_inspecao, id_pergunta, valor_resposta, observacao) VALUES 
(1, 1, 1, 'sim', 'Calibragem verificada a 32 psi.'),
(2, 1, 2, 'sim', 'Óleo verificado na vareta.'),
(3, 1, 3, '14250', 'Painel confere com o sistema.'),
(4, 1, 4, 'Nenhum arranhão novo.', 'Lataria limpa e preservada.');


-- =========================================================
-- 9. EVIDÊNCIAS
-- =========================================================
INSERT INTO Evidencias (id_evidencia, id_resposta, caminho_arquivo, nome_arquivo) VALUES 
(1, 4, 'uploads/evidencias/veiculo_1_lataria.jpg', 'veiculo_1_lataria.jpg');


-- =========================================================
-- 10. OCORRÊNCIAS
-- =========================================================
-- (Neste exemplo simulado, a inspeção ocorreu sem ocorrências abertas, demonstrando veículo saudável)