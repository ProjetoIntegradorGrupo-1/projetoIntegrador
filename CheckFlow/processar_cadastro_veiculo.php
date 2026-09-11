<?php
// processar_cadastro_veiculo.php
session_start();
require_once 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $placa         = trim($_POST['placa']);
    $marca_modelo  = trim($_POST['marca_modelo']); // Ajuste o name conforme o formulário html se necessário ('marca' / 'modelo')
    $ano           = trim($_POST['ano']);
    $status        = isset($_POST['status']) ? trim($_POST['status']) : 'ativo';

    try {
        // Insere o veículo na tabela Veiculos baseada no nosso DDL inicial do Axion
        $sql = "INSERT INTO Veiculos (placa, marca_modelo, ano, status) 
                VALUES (:placa, :marca_modelo, :ano, :status)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':placa', $placa);
        $stmt->bindParam(':marca_modelo', $marca_modelo);
        $stmt->bindParam(':ano', $ano);
        $stmt->bindParam(':status', $status);
        
        $stmt->execute();

        header("Location: oqfazer.php?sucesso=veiculo_cadastrado");
        exit;

    } catch (PDOException $e) {
        echo "<script>
                alert('Erro ao cadastrar veículo (Placa já existente?): " . addslashes($e->getMessage()) . "');
                window.location.href = 'cadcar.html';
              </script>";
        exit;
    }

} else {
    header("Location: oqfazer.php");
    exit;
}
?>  