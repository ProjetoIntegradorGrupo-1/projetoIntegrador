<?php
// processar_cadastro_veiculo.php
require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/backend/auth.php';
require_once __DIR__ . '/backend/fotos.php';
axion_exigir_perfil(['admin', 'supervisor']);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $marca        = trim($_POST['marca'] ?? '');
    $modelo       = trim($_POST['modelo'] ?? '');
    $marca_modelo = !empty($_POST['marca_modelo']) ? trim($_POST['marca_modelo']) : trim("$marca $modelo");
    $ano          = (int)($_POST['ano'] ?? date('Y'));
    $anoAtual     = (int)date('Y');
    if ($ano > $anoAtual) {
        echo "<script>alert('Operação bloqueada: Não é permitido cadastrar veículo com ano de fabricação futuro ($ano). O ano máximo permitido é $anoAtual.'); window.history.back();</script>";
        exit;
    }
    if ($ano < 1980) {
        echo "<script>alert('Ano de fabricação inválido ($ano). Informe um ano a partir de 1980.'); window.history.back();</script>";
        exit;
    }
    $placa        = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $_POST['placa'] ?? ''));
    $cor          = trim($_POST['cor'] ?? '');
    $kmrodado     = (int)($_POST['kmrodado'] ?? 0);
    $renavam      = preg_replace('/\D/', '', $_POST['renavam'] ?? '');
    $chassi       = strtoupper(trim($_POST['chassi'] ?? ''));
    $status       = isset($_POST['status']) ? trim($_POST['status']) : 'ativo';
    if (!in_array($status, ['ativo', 'em_rota', 'manutencao'], true)) $status = 'ativo';
    $finalidade   = ($_POST['finalidade'] ?? '') === 'locacao' ? 'locacao' : 'frota';
    axion_validar_csrf();
    // Evita carro repetido: mesma placa, Renavam ou chassi
    $dup = $pdo->prepare("SELECT COUNT(*) FROM Veiculos WHERE placa = :placa OR (:ren <> '' AND renavam = :ren2) OR (:ch <> '' AND chassi = :ch2)");
    $dup->execute([':placa' => $placa, ':ren' => $renavam, ':ren2' => $renavam, ':ch' => $chassi, ':ch2' => $chassi]);
    if ((int)$dup->fetchColumn() > 0) {
        echo "<script>alert('Já existe um veículo com esta placa, Renavam ou chassi.'); window.history.back();</script>";
        exit;
    }

    try {
        $sql = "INSERT INTO Veiculos (placa, marca, modelo, marca_modelo, ano, cor, km_rodado, renavam, chassi, status, finalidade)
                VALUES (:placa, :marca, :modelo, :marca_modelo, :ano, :cor, :km_rodado, :renavam, :chassi, :status, :finalidade)";

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
            ':status'       => $status,
            ':finalidade'   => $finalidade
        ]);

        // Foto enviada junto com o cadastro (opcional)
        $novoId = (int)$pdo->lastInsertId();
        try {
            $foto = axion_receber_foto('foto', 'veiculo', $novoId, 5);
            if ($foto) $pdo->prepare("UPDATE Veiculos SET foto = :f WHERE id_veiculo = :id")->execute([':f' => $foto, ':id' => $novoId]);
        } catch (RuntimeException $e) {
            // O cadastro foi feito; só a foto não foi aceita
        }
        header("Location: oqfazer.php?aba=veiculos&sucesso=veiculo_cadastrado");
        exit;

    } catch (PDOException $e) {
        echo "<script>alert('Erro ao cadastrar veículo.'); window.history.back();</script>";
        exit;
    }
} else {
    header("Location: oqfazer.php");
    exit;
}
?>
