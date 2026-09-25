<?php
require_once __DIR__ . '/../auth/verificar_sessao.php';
require_once __DIR__ . '/../config/conexao.php';

// Apenas administradores e gestores podem gerenciar a frota
exigirPerfil(['administrador', 'gestor']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../Frontend/editarVeiculo.php');
    exit;
}

$id_veiculo = filter_input(INPUT_POST, 'id_veiculo', FILTER_VALIDATE_INT);
if (!$id_veiculo) {
    header('Location: ../../Frontend/editarVeiculo.php?erro=veiculo_invalido');
    exit;
}

// Limpeza e padronização dos dados recebidos
$placa = strtoupper(trim($_POST['placa'] ?? ''));
$marca = trim($_POST['marca'] ?? '');
$modelo = trim($_POST['modelo'] ?? '');
$ano = (int)($_POST['ano'] ?? 0);
$cor = trim($_POST['cor'] ?? '');
$quilometragem = (int)($_POST['quilometragem'] ?? 0);
$renavam = preg_replace('/[^0-9]/', '', $_POST['renavam'] ?? '');
$chassi = strtoupper(trim($_POST['chassi'] ?? ''));
$status = trim($_POST['status'] ?? 'ativo');

// Validação básica obrigatória
if (empty($placa) || empty($marca) || empty($modelo) || empty($ano)) {
    header('Location: ../../Frontend/editarVeiculo.php?erro=campos_obrigatorios');
    exit;
}

try {
    $sql = "UPDATE Veiculos SET 
                placa = ?, 
                marca = ?, 
                modelo = ?, 
                ano = ?, 
                cor = ?, 
                quilometragem = ?, 
                renavam = ?, 
                chassi = ?, 
                status = ? 
            WHERE id_veiculo = ?";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $placa, 
        $marca,
        $modelo, 
        $ano, 
        $cor, 
        $quilometragem, 
        empty($renavam) ? null : $renavam, 
        empty($chassi) ? null : $chassi,
        $status,
        $id_veiculo
    ]);

    header('Location: ../php/oqfazer.php?sucesso=veiculo_atualizado');
    exit;

} catch (PDOException $e) {
    error_log("Erro ao atualizar veículo ID {$id_veiculo}: " . $e->getMessage());
    header('Location: ../../Frontend/editarVeiculo.php?erro=falha_banco');
    exit;
}