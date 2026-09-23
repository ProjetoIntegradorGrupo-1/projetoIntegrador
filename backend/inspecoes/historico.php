<?php
require_once __DIR__ . '/../auth/verificar_sessao.php';
require_once __DIR__ . '/../config/conexao.php';

exigirLogin();

// Consulta relacional para montar o histórico completo
$sql = "SELECT i.id_inspecao, i.data_inicio, i.data_finalizacao, i.status, 
               v.placa, v.marca, v.modelo, 
               c.titulo AS checklist_titulo, 
               u.nome AS usuario_responsavel
        FROM Inspecoes i
        JOIN Veiculos v ON i.id_veiculo = v.id_veiculo
        JOIN Checklists c ON i.id_checklist = c.id_checklist
        JOIN Usuarios u ON i.id_usuario = u.id_usuario
        ORDER BY i.data_inicio DESC";

try {
    $stmt = $pdo->query($sql);
    $inspecoes = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao carregar histórico: " . $e->getMessage());
    $inspecoes = [];
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <th>Ações</th>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico de Inspeções</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

    <div class="container py-5">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Histórico de Inspeções</h4>
                <a href="../php/oqfazer.php" class="btn btn-sm btn-outline-light">Voltar ao Menu</a>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Data/Hora Início</th>
                                <th>Veículo</th>
                                <th>Checklist</th>
                                <th>Responsável</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($inspecoes)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">Nenhuma inspeção encontrada no
                                        sistema.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($inspecoes as $i): ?>
                                    <tr>
                                        <td>#<?= $i['id_inspecao'] ?></td>
                                        <td><?= date('d/m/Y H:i', strtotime($i['data_inicio'])) ?></td>
                                        <td><?= htmlspecialchars($i['placa'] . ' - ' . $i['marca']) ?></td>
                                        <td><?= htmlspecialchars($i['checklist_titulo']) ?></td>
                                        <td><?= htmlspecialchars($i['usuario_responsavel']) ?></td>
                                        <td>
                                            <?php if ($i['status'] === 'finalizada'): ?>
                                                <span class="badge bg-success">Finalizada</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">Rascunho</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>