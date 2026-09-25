<?php
session_start();

require_once __DIR__ . '/../auth/verificar_sessao.php';
require_once __DIR__ . '/../config/conexao.php';

// Apenas administradores e gestores podem excluir veículos
exigirPerfil(['administrador', 'gestor']);

// Garante que a requisição foi feita via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../Frontend/excluirVeiculo.php');
    exit;
}

$id_veiculo = filter_input(INPUT_POST, 'id_veiculo', FILTER_VALIDATE_INT);

if (!$id_veiculo) {
    header('Location: ../../Frontend/excluirVeiculo.php?erro=veiculo_invalido');
    exit;
}

try {
    // Verifica se o veículo existe antes de prosseguir
    $stmtCheck = $pdo->prepare("SELECT id_veiculo FROM Veiculos WHERE id_veiculo = ? LIMIT 1");
    $stmtCheck->execute([$id_veiculo]);
    
    if (!$stmtCheck->fetch()) {
        header('Location: ../../Frontend/excluirVeiculo.php?erro=nao_encontrado');
        exit;
    }

    // Exclusão Lógica: Atualiza o status do veículo para 'inativo' para preservar o histórico de vistorias
    $sqlDelete = "UPDATE Veiculos SET status = 'inativo' WHERE id_veiculo = ?";
    $stmtDelete = $pdo->prepare($sqlDelete);
    $stmtDelete->execute([$id_veiculo]);

    // Redireciona com sucesso
    header('Location: ../php/oqfazer.php?sucesso=veiculo_excluido');
    exit;

} catch (PDOException $e) {
    error_log("Erro ao excluir veículo: " . $e->getMessage());
    header('Location: ../../Frontend/excluirVeiculo.php?erro=falha_banco');
    exit;
}