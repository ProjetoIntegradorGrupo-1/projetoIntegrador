<?php
// processar_cadastro_veiculo.php
session_start();
require_once 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $marca        = trim($_POST['marca'] ?? '');
    $modelo       = trim($_POST['modelo'] ?? '');
    $marca_modelo = !empty($_POST['marca_modelo']) ? trim($_POST['marca_modelo']) : trim("$marca $modelo");
    $ano          = (int)($_POST['ano'] ?? date('Y'));
    $placa        = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $_POST['placa'] ?? ''));
    $cor          = trim($_POST['cor'] ?? '');
    $kmrodado     = (int)($_POST['kmrodado'] ?? 0);
    $renavam      = preg_replace('/\D/', '', $_POST['renavam'] ?? '');
    $chassi       = strtoupper(trim($_POST['chassi'] ?? ''));
    $status       = isset($_POST['status']) ? trim($_POST['status']) : 'ativo';

    if (strlen($renavam) !== 11) {
        echo "<script>alert('Renavam inválido! O Renavam deve conter exatamente 11 dígitos.'); window.history.back();</script>";
        exit;
    }
    if (strlen($chassi) !== 17) {
        echo "<script>alert('Chassi inválido! O Chassi deve conter exatamente 17 caracteres.'); window.history.back();</script>";
        exit;
    }

    try {
        $sql = "INSERT INTO Veiculos (placa, marca, modelo, marca_modelo, ano, cor, km_rodado, renavam, chassi, status)
                VALUES (:placa, :marca, :modelo, :marca_modelo, :ano, :cor, :km_rodado, :renavam, :chassi, :status)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':placa'        => $placa,
            ':marca'        => $marca,
            ':modelo'       => $modelo,
            ':marca_modelo' => $marca_modelo,
            ':ano'          => $ano,
            ':cor'          => $cor,
            ':km_rodado'    => $kmrodado,
            ':renavam'      => $renavam,
            ':chassi'       => $chassi,
            ':status'       => $status
        ]);

        header("Location: oqfazer.php?aba=veiculos&sucesso=veiculo_cadastrado");
        exit;

    } catch (PDOException $e) {
        echo "<script>
                alert('Erro ao cadastrar veículo (Placa já existente?): " . addslashes($e->getMessage()) . "');
                window.history.back();
              </script>";
        exit;
    }
} else {
    header("Location: oqfazer.php");
    exit;
}
?>
