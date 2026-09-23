<?php
require_once __DIR__ . '/../auth/verificar_sessao.php';
require_once __DIR__ . '/../config/conexao.php';

exigirPerfil(['administrador', 'gestor']);

try {
    // Busca todas as ocorrências cruzando com veículo e inspeção
    $sql = "SELECT o.id_ocorrencia, o.descricao, o.status, o.data_registro, 
                   v.placa, v.marca, v.modelo, i.id_inspecao
            FROM Ocorrencias o
            JOIN Inspecoes i ON o.id_inspecao = i.id_inspecao
            JOIN Veiculos v ON i.id_veiculo = v.id_veiculo
            ORDER BY o.data_registro DESC";

    $ocorrencias = $pdo->query($sql)->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao carregar ocorrências: " . $e->getMessage());
    $ocorrencias = [];
}
?>
<!DOCTYPE html>
<html lang="pt-pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Ocorrências</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

    <div class="container py-5">
        <div class="card shadow-sm">
            <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Ocorrências e Defeitos Registados</h4>
                <a href="../php/oqfazer.php" class="btn btn-sm btn-outline-light">Voltar ao Menu</a>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Veículo</th>
                                <th>Descrição da Ocorrência</th>
                                <th>Data</th>
                                <th>Inspeção</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($ocorrencias)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">Nenhuma ocorrência registada no
                                        sistema. Parabéns à frota!</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($ocorrencias as $o): ?>
                                    <tr>
                                        <td>#<?= $o['id_ocorrencia'] ?></td>
                                        <td><?= htmlspecialchars($o['placa'] . ' - ' . $o['marca'] . ' ' . $o['modelo']) ?></td>
                                        <td><?= htmlspecialchars($o['descricao']) ?></td>
                                        <td><?= date('d/m/Y H:i', strtotime($o['data_registro'])) ?></td>
                                        <td><a
                                                href="../inspecoes/detalhes_inspecao.php?id=<?= $o['id_inspecao'] ?>">#<?= $o['id_inspecao'] ?></a>
                                        </td>
                                        <td>
                                            <?php if ($o['status'] === 'aberta'): ?>
                                                <span class="badge bg-danger">Aberta</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">Resolvida</span>
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