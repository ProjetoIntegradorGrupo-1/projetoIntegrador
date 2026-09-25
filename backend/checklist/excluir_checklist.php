<?php
require_once __DIR__ . '/../auth/verificar_sessao.php';
require_once __DIR__ . '/../config/conexao.php';

// Apenas administradores e gestores podem excluir ou inativar checklists
exigirPerfil(['administrador', 'gestor']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../Frontend/oqfazer.php');
    exit;
}

$id_checklist = filter_input(INPUT_POST, 'id_checklist', FILTER_VALIDATE_INT);

if (!$id_checklist) {
    header('Location: ../../Frontend/oqfazer.php?erro=checklist_invalido');
    exit;
}

try {
    // Verifica se o checklist existe antes de prosseguir
    $stmtCheck = $pdo->prepare("SELECT id_checklist FROM Checklists WHERE id_checklist = ? LIMIT 1");
    $stmtCheck->execute([$id_checklist]);
    
    if (!$stmtCheck->fetch()) {
        header('Location: ../../Frontend/oqfazer.php?erro=nao_encontrado');
        exit;
    }

    // Exclusão Lógica: Altera o status para 'inativo' preservando as respostas e o histórico associado
    $sqlDelete = "UPDATE Checklists SET status = 'inativo' WHERE id_checklist = ?";
    $stmtDelete = $pdo->prepare($sqlDelete);
    $stmtDelete->execute([$id_checklist]);

    header('Location: ../php/oqfazer.php?sucesso=checklist_excluido');
    exit;

} catch (PDOException $e) {
    error_log("Erro ao excluir checklist ID {$id_checklist}: " . $e->getMessage());
    header('Location: ../../Frontend/oqfazer.php?erro=falha_banco');
    exit;
}