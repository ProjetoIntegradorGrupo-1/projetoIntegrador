<?php
require_once __DIR__ . '/../auth/verificar_sessao.php';
require_once __DIR__ . '/../config/conexao.php';

exigirLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: nova_inspecao.php');
    exit;
}

$id_veiculo = $_POST['id_veiculo'] ?? null;
$id_checklist = $_POST['id_checklist'] ?? null;
$quilometragem = !empty($_POST['quilometragem']) ? (int) $_POST['quilometragem'] : null;
$id_usuario = $_SESSION['id_usuario'];

if (!$id_veiculo || !$id_checklist) {
    header('Location: nova_inspecao.php?erro=dados_incompletos');
    exit;
}

try {
    // Regista a inspeção central com o estado 'rascunho'
    $sql = "INSERT INTO Inspecoes (id_checklist, id_veiculo, id_usuario, quilometragem, status) 
            VALUES (?, ?, ?, ?, 'rascunho')";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_checklist, $id_veiculo, $id_usuario, $quilometragem]);

    // Captura o ID da inspeção que acabou de ser criada
    $id_inspecao = $pdo->lastInsertId();

    // Redireciona para o ecrã de execução (que criaremos a seguir), passando o ID na URL
    header("Location: executar_inspecao.php?id=" . $id_inspecao);
    exit;

} catch (PDOException $e) {
    error_log("Erro ao iniciar inspeção: " . $e->getMessage());
    header('Location: nova_inspecao.php?erro=falha_banco');
    exit;
}