<?php
// backend/processar_aprovacao_checklist.php
header('Content-Type: application/json; charset=utf-8');
session_start();

if (!isset($_SESSION['id_usuario'])) {
    http_response_code(401);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Sessão expirada.']);
    exit;
}

$perfil = $_SESSION['perfil_usuario'] ?? 'motorista';
if ($perfil !== 'gestor') {
    http_response_code(403);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Apenas o Gestor Administrador tem permissão para validar ou devolver modelos de checklist.']);
    exit;
}

require_once __DIR__ . '/conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Método inválido.']);
    exit;
}

try {
    $id_checklist = intval($_POST['id_checklist'] ?? 0);
    $acao         = trim($_POST['acao'] ?? '');
    $motivo       = trim($_POST['motivo_ajuste'] ?? '');
    $id_gestor    = $_SESSION['id_usuario'];

    if ($id_checklist <= 0 || !in_array($acao, ['aprovar', 'solicitar_ajuste'])) {
        throw new Exception('Parâmetros inválidos para avaliação do checklist.');
    }

    if ($acao === 'aprovar') {
        $stmt = $pdo->prepare("UPDATE Checklists 
                               SET status = 'ativo', 
                                   aprovado_por = :gestor, 
                                   data_aprovacao = NOW(), 
                                   motivo_ajuste = NULL 
                               WHERE id_checklist = :id");
        $stmt->execute([':gestor' => $id_gestor, ':id' => $id_checklist]);

        echo json_encode([
            'sucesso'  => true,
            'mensagem' => 'Checklist validado e colocado em produção com sucesso!',
            'novo_status' => 'ativo',
            'status_label' => 'Ativo (Em Produção)'
        ]);
    } else {
        if (empty($motivo)) {
            throw new Exception('Informe o motivo ou as recomendações de ajuste para o supervisor.');
        }

        $stmt = $pdo->prepare("UPDATE Checklists 
                               SET status = 'ajuste_solicitado', 
                                   motivo_ajuste = :motivo 
                               WHERE id_checklist = :id");
        $stmt->execute([':motivo' => $motivo, ':id' => $id_checklist]);

        echo json_encode([
            'sucesso'  => true,
            'mensagem' => 'Checklist devolvido ao supervisor para adequações.',
            'novo_status' => 'ajuste_solicitado',
            'status_label' => 'Ajuste Solicitado'
        ]);
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['sucesso' => false, 'mensagem' => $e->getMessage()]);
}

