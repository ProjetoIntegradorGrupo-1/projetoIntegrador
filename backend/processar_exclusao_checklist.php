<?php
// backend/processar_exclusao_checklist.php
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../frontend/index.html");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_checklist = intval($_POST['id_checklist'] ?? 0);

    if ($id_checklist <= 0) {
        echo "<script>alert('ID de checklist inválido.'); window.history.back();</script>";
        exit;
    }

    try {
        // Desativação lógica do modelo (status 'inativo')
        $stmt = $pdo->prepare("UPDATE Checklists SET status = 'inativo' WHERE id_checklist = :id");
        $stmt->bindParam(':id', $id_checklist, PDO::PARAM_INT);
        $stmt->execute();

        header("Location: ../frontend/oqfazer.php?sucesso=checklist_excluido");
        exit;

    } catch (PDOException $e) {
        echo "<script>
                alert('Erro ao excluir checklist: " . addslashes($e->getMessage()) . "');
                window.history.back();
              </script>";
        exit;
    }

} else {
    header("Location: ../frontend/oqfazer.php");
    exit;
}
?>

