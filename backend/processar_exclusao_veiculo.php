<?php
// backend/processar_exclusao_veiculo.php
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../frontend/index.html");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_veiculo = intval($_POST['id_veiculo'] ?? 0);

    if ($id_veiculo <= 0) {
        echo "<script>alert('ID de veículo inválido.'); window.history.back();</script>";
        exit;
    }

    try {
        // Desativação lógica do veículo (status 'inativo')
        $stmt = $pdo->prepare("UPDATE Veiculos SET status = 'inativo' WHERE id_veiculo = :id");
        $stmt->bindParam(':id', $id_veiculo, PDO::PARAM_INT);
        $stmt->execute();

        header("Location: ../frontend/oqfazer.php?sucesso=veiculo_excluido");
        exit;

    } catch (PDOException $e) {
        echo "<script>
                alert('Erro ao excluir veículo: " . addslashes($e->getMessage()) . "');
                window.history.back();
              </script>";
        exit;
    }

} else {
    header("Location: ../frontend/oqfazer.php");
    exit;
}
?>

