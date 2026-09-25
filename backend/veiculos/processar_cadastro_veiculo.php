<?php
require_once __DIR__ . '/../auth/verificar_sessao.php';
require_once __DIR__ . '/../config/conexao.php';

// Apenas administradores e gestores gerenciam a frota
exigirPerfil(['administrador', 'gestor']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../Frontend/cadcar.html');
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

// Validação básica obrigatória
if (empty($placa) || empty($marca) || empty($modelo) || empty($ano)) {
    header('Location: ../../Frontend/cadcar.html?erro=campos_obrigatorios');
    exit;
}

try {
    $sql = "INSERT INTO Veiculos (placa, marca, modelo, ano, cor, quilometragem, renavam, chassi, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'ativo')";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $placa, 
        $marca,
        $modelo, 
        $ano, 
        $cor, 
        $quilometragem, 
        empty($renavam) ? null : $renavam, 
        empty($chassi) ? null : $chassi
    ]);

    header('Location: ../php/oqfazer.php?sucesso=veiculo_cadastrado');
    exit;

} catch (PDOException $e) {
    error_log("Erro ao cadastrar veículo: " . $e->getMessage());
    header('Location: ../../Frontend/cadcar.html?erro=falha_banco');
    exit;
}