<?php
// backend/processar_exclusao_usuario.php
require_once 'conexao.php';
require_once 'auth_check.php';

// Apenas o Gestor pode realizar a exclusão lógica de usuários
autorizarAcesso(['gestor']);


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_usuario = intval($_POST['id_usuario'] ?? 0);

    if ($id_usuario <= 0) {
        echo "<script>alert('ID de usuário inválido.'); window.history.back();</script>";
        exit;
    }

    try {
        // Desativação segura (Soft Delete)
        $stmt = $pdo->prepare("UPDATE Usuarios SET ativo = 0 WHERE id_usuario = :id");
        $stmt->bindParam(':id', $id_usuario, PDO::PARAM_INT);
        $stmt->execute();

        header("Location: ../frontend/oqfazer.php?sucesso=usuario_excluido");
        exit;

    } catch (PDOException $e) {
        echo "<script>
                alert('Erro ao excluir usuário: " . addslashes($e->getMessage()) . "');
                window.history.back();
              </script>";
        exit;
    }

} else {
    header("Location: ../frontend/oqfazer.php");
    exit;
}
?>

