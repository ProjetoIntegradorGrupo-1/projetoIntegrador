<?php
require_once __DIR__ . '/../auth/verificar_sessao.php';
require_once __DIR__ . '/../config/conexao.php';

// Apenas administradores e gestores gerenciam não conformidades
exigirPerfil(['administrador', 'gestor']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../Frontend/detalhesNaoConformidade.php');
    exit;
}

$id_nao_conformidade = filter_input(INPUT_POST, 'id_nao_conformidade', FILTER_VALIDATE_INT);
$status = trim($_POST['status'] ?? '');
$observacao_resolucao = trim($_POST['observacao_resolucao'] ?? '');

if (!$id_nao_conformidade || empty($status)) {
    header('Location: ../../Frontend/detalhesNaoConformidade.php?erro=dados_invalidos');
    exit;
}

try {
    // Atualiza o status da não conformidade e registra quem resolveu / quando foi resolvido
    $id_usuario_responsavel = $_SESSION['id_usuario'];

    $sql = "UPDATE NaoConformidades 
            SET status = ?, 
                observacao_resolucao = ?, 
                id_responsavel = ?, 
                data_resolucao = NOW() 
            WHERE id_nao_conformidade = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $status, 
        empty($observacao_resolucao) ? null : $observacao_resolucao, 
        $id_usuario_responsavel, 
        $id_nao_conformidade
    ]);

    header('Location: ../php/oqfazer.php?sucesso=naoconformidade_atualizada');
    exit;

} catch (PDOException $e) {
    error_log("Erro ao atualizar não conformidade ID {$id_nao_conformidade}: " . $e->getMessage());
    header('Location: ../../Frontend/detalhesNaoConformidade.php?erro=falha_banco');
    exit;
}