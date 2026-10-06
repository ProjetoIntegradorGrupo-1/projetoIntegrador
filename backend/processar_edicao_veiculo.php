<?php
// backend/processar_edicao_veiculo.php
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../frontend/index.html");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_veiculo    = intval($_POST['id_veiculo'] ?? 0);
    $placa         = strtoupper(trim($_POST['placa'] ?? ''));
    $marca         = trim($_POST['marca'] ?? '');
    $modelo        = trim($_POST['modelo'] ?? '');
    $marca_modelo  = trim($marca . ' ' . $modelo);
    $ano           = intval($_POST['ano'] ?? 0);
    $cor           = trim($_POST['cor'] ?? '');
    $km_rodado     = intval($_POST['kmrodado'] ?? 0);
    $renavam       = trim($_POST['renavam'] ?? '');
    $chassi        = strtoupper(trim($_POST['chassi'] ?? ''));

    if ($id_veiculo <= 0 || empty($placa) || empty($marca_modelo)) {
        echo "<script>alert('Campos obrigatórios do veículo ausentes.'); window.history.back();</script>";
        exit;
    }

    try {
        $sql = "UPDATE Veiculos 
                SET placa = :placa, marca = :marca, modelo = :modelo, marca_modelo = :marca_modelo,
                    ano = :ano, cor = :cor, km_rodado = :km, renavam = :renavam, chassi = :chassi
                WHERE id_veiculo = :id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':placa', $placa);
        $stmt->bindParam(':marca', $marca);
        $stmt->bindParam(':modelo', $modelo);
        $stmt->bindParam(':marca_modelo', $marca_modelo);
        $stmt->bindParam(':ano', $ano, PDO::PARAM_INT);
        $stmt->bindParam(':cor', $cor);
        $stmt->bindParam(':km', $km_rodado, PDO::PARAM_INT);
        $stmt->bindParam(':renavam', $renavam);
        $stmt->bindParam(':chassi', $chassi);
        $stmt->bindParam(':id', $id_veiculo, PDO::PARAM_INT);
        $stmt->execute();

        header("Location: ../frontend/oqfazer.php?sucesso=veiculo_editado");
        exit;

    } catch (PDOException $e) {
        echo "<script>
                alert('Erro ao editar veículo: " . addslashes($e->getMessage()) . "');
                window.history.back();
              </script>";
        exit;
    }

} else {
    header("Location: ../frontend/oqfazer.php");
    exit;
}
?>

