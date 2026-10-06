<?php
// backend/processar_despacho_ocorrencia.php
header('Content-Type: application/json; charset=utf-8');
session_start();

if (!isset($_SESSION['id_usuario'])) {
    http_response_code(401);
    echo json_encode(['sucesso' => false, 'erro' => 'Sessão expirada. Faça login novamente.']);
    exit;
}

$perfilAtual = $_SESSION['perfil_usuario'] ?? 'motorista';
if (!in_array($perfilAtual, ['gestor', 'supervisor'])) {
    http_response_code(403);
    echo json_encode(['sucesso' => false, 'erro' => 'Acesso negado. Apenas gestores e supervisores podem despachar ocorrências.']);
    exit;
}

require_once __DIR__ . '/conexao.php';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['sucesso' => false, 'erro' => 'Método inválido.']);
    exit;
}

$id_ocorrencia = intval($_POST['id_ocorrencia'] ?? 0);
$acao = trim($_POST['acao'] ?? '');
$observacao = trim($_POST['observacao'] ?? '');

if ($id_ocorrencia <= 0 || empty($acao)) {
    http_response_code(400);
    echo json_encode(['sucesso' => false, 'erro' => 'Parâmetros insuficientes.']);
    exit;
}

try {
    // Busca a ocorrência atual
    $stmt = $pdo->prepare("SELECT * FROM Ocorrencias WHERE id_ocorrencia = :id LIMIT 1");
    $stmt->execute([':id' => $id_ocorrencia]);
    $ocorrencia = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ocorrencia) {
        http_response_code(404);
        echo json_encode(['sucesso' => false, 'erro' => 'Ocorrência não encontrada.']);
        exit;
    }

    $nomeUsuario = $_SESSION['nome_usuario'] ?? 'Gestor';
    $agora = date('d/m/Y H:i');

    if ($acao === 'enviar_oficina') {
        $novoStatus = 'em_oficina';
        $novoStatusVeiculo = 'retido_oficina';
        $obsDespacho = "Despachado para oficina credenciada por $nomeUsuario em $agora." . ($observacao ? " Obs: $observacao" : "");

        $update = $pdo->prepare("UPDATE Ocorrencias SET status = :st, status_veiculo = :sv, observacao_despacho = :obs WHERE id_ocorrencia = :id");
        $update->execute([
            ':st' => $novoStatus,
            ':sv' => $novoStatusVeiculo,
            ':obs' => $obsDespacho,
            ':id' => $id_ocorrencia
        ]);

        // Se houver veículo vinculado, atualiza status do veículo para 'manutencao'
        if (!empty($ocorrencia['id_veiculo'])) {
            $pdo->prepare("UPDATE Veiculos SET status = 'manutencao' WHERE id_veiculo = :idv")
                ->execute([':idv' => $ocorrencia['id_veiculo']]);
        }

        echo json_encode([
            'sucesso' => true,
            'mensagem' => 'Veículo enviado para a oficina com sucesso. Status atualizado.',
            'novo_status' => 'em_oficina',
            'status_label' => 'Em Oficina',
            'status_veiculo' => 'retido_oficina',
            'status_veiculo_label' => 'Retido na Oficina'
        ]);
        exit;

    } elseif ($acao === 'aprovar_reparo') {
        $novoStatus = 'resolvida';
        $novoStatusVeiculo = 'liberado';
        $obsDespacho = "Reparo aprovado e liberação concedida por $nomeUsuario em $agora." . ($observacao ? " Obs: $observacao" : "");

        $update = $pdo->prepare("UPDATE Ocorrencias SET status = :st, status_veiculo = :sv, data_resolucao = NOW(), observacao_despacho = :obs WHERE id_ocorrencia = :id");
        $update->execute([
            ':st' => $novoStatus,
            ':sv' => $novoStatusVeiculo,
            ':obs' => $obsDespacho,
            ':id' => $id_ocorrencia
        ]);

        // Se não houver outras ocorrências abertas para este veículo, libera o veículo
        if (!empty($ocorrencia['id_veiculo'])) {
            $checkOutras = $pdo->prepare("SELECT COUNT(*) FROM Ocorrencias WHERE id_veiculo = :idv AND status != 'resolvida' AND id_ocorrencia != :id");
            $checkOutras->execute([':idv' => $ocorrencia['id_veiculo'], ':id' => $id_ocorrencia]);
            if ($checkOutras->fetchColumn() == 0) {
                $pdo->prepare("UPDATE Veiculos SET status = 'ativo' WHERE id_veiculo = :idv")
                    ->execute([':idv' => $ocorrencia['id_veiculo']]);
            }
        }

        echo json_encode([
            'sucesso' => true,
            'mensagem' => 'Reparo aprovado com sucesso! Veículo liberado para circulação.',
            'novo_status' => 'resolvida',
            'status_label' => 'Resolvida',
            'status_veiculo' => 'liberado',
            'status_veiculo_label' => 'Liberado para Circulação'
        ]);
        exit;

    } else {
        http_response_code(400);
        echo json_encode(['sucesso' => false, 'erro' => 'Ação de despacho não reconhecida.']);
        exit;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'erro' => 'Erro interno ao processar despacho: ' . $e->getMessage()]);
    exit;
}

