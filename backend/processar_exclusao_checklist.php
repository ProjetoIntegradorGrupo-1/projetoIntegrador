<?php
// backend/processar_exclusao_checklist.php
require_once 'conexao.php';
require_once 'auth_check.php';

// Apenas o Gestor pode realizar a exclusão lógica de modelos de checklist
autorizarAcesso(['gestor']);


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

