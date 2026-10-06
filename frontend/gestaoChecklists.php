<?php
// frontend/gestaoChecklists.php
header('Content-Type: text/html; charset=utf-8');
session_start();

require_once __DIR__ . '/../backend/conexao.php';
require_once __DIR__ . '/../backend/auth_check.php';

// Apenas Gestores e Supervisores podem acessar a gestão de modelos de checklist
autorizarAcesso(['gestor', 'supervisor']);

$perfilUsuario = $_SESSION['perfil_usuario'] ?? 'supervisor';
$idUsuarioLogado = $_SESSION['id_usuario'];

// Busca todos os checklists com dados do criador e aprovador
$sql = "SELECT c.*, 
               uCriador.nome AS nome_criador, uCriador.perfil AS perfil_criador,
               uAprov.nome AS nome_aprovador
        FROM Checklists c
        LEFT JOIN Usuarios uCriador ON c.id_criador = uCriador.id_usuario
        LEFT JOIN Usuarios uAprov ON c.aprovado_por = uAprov.id_usuario
        ORDER BY 
            CASE WHEN c.status = 'pendente_aprovacao' THEN 1 
                 WHEN c.status = 'ajuste_solicitado' THEN 2 
                 ELSE 3 END, 
            c.id_checklist DESC";

$stmt = $pdo->query($sql);
$checklists = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Busca perguntas de cada checklist
$stmtPerg = $pdo->query("SELECT id_pergunta, id_checklist, texto_pergunta, tipo_resposta, categoria, ordem 
                         FROM Perguntas 
                         ORDER BY ordem ASC, id_pergunta ASC");
$todasPerguntas = $stmtPerg->fetchAll(PDO::FETCH_ASSOC);

$mapaPerguntas = [];
foreach ($todasPerguntas as $p) {
    $mapaPerguntas[$p['id_checklist']][] = $p;
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Governança e Aprovação de Checklists - Axion</title>
    <!-- Google Fonts: Inter & Roboto Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Roboto+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- Bootstrap 5.3 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; color: #1e293b; }
        .font-mono { font-family: 'Roboto Mono', monospace !important; }
        .card-checklist { border: 1px solid #e2e8f0; border-radius: 12px; transition: transform 0.2s, box-shadow 0.2s; }
        .card-checklist:hover { box-shadow: 0 6px 18px rgba(0,0,0,0.06); }
        .badge-pendente { background-color: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .badge-ativo { background-color: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .badge-ajuste { background-color: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
    </style>
</head>

<body>

    <!-- NAVBAR SUPERIOR -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark py-2 px-3 shadow-sm sticky-top">
        <div class="container-fluid max-w-1200 mx-auto" style="max-width: 1200px;">
            <div class="d-flex align-items-center gap-2">
                <a href="oqfazer.php" class="btn btn-outline-light btn-sm px-2 py-1" title="Voltar ao Menu">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <span class="navbar-brand fw-bold mb-0 fs-5">AXION • Governança de Checklists</span>
            </div>
            <div class="d-flex align-items-center gap-2 text-white small">
                <span class="badge <?= $perfilUsuario === 'gestor' ? 'bg-primary' : 'bg-info text-dark' ?> text-uppercase">
                    <?= htmlspecialchars($perfilUsuario) ?>
                </span>
                <span class="d-none d-md-inline"><?= htmlspecialchars($_SESSION['nome_usuario']) ?></span>
            </div>
        </div>
    </nav>

    <div class="container py-4" style="max-width: 1200px;">

        <!-- TÍTULO E AÇÕES -->
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h1 class="h3 fw-bold text-dark mb-1">Aprovação e Validação de Modelos</h1>
                <p class="text-muted small mb-0">Controle de qualidade e dupla validação (Supervisor propõe ➔ Gestor valida)</p>
            </div>
            <div>
                <a href="criarChecklist.php" class="btn btn-primary shadow-sm">
                    <i class="bi bi-plus-circle me-1"></i> Propor Novo Checklist
                </a>

            </div>
        </div>

        <!-- MENSAGEM EXPLICATIVA DE PAPÉIS -->
        <div class="alert alert-info border-info-subtle shadow-sm mb-4">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-shield-check fs-4 text-primary"></i>
                <div class="small">
                    <strong>Regra de Governança RBAC:</strong>
                    <?php if ($perfilUsuario === 'gestor'): ?>
                        Como <strong>Gestor Administrador</strong>, cabe a você avaliar as perguntas dos modelos criados pelos supervisores, validar para produção imediata ou devolver com apontamentos de ajustes.
                    <?php else: ?>
                        Como <strong>Supervisor</strong>, seus checklists cadastrados entram em análise prévia. Assim que o Gestor validar o modelo, ele ficará disponível automaticamente para a equipe de campo no pátio.
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- LISTAGEM DE CHECKLISTS -->
        <div class="row g-4">
            <?php if (empty($checklists)): ?>
                <div class="col-12 text-center py-5 text-muted">
                    <i class="bi bi-journal-x fs-1 d-block mb-2"></i>
                    Nenhum modelo de checklist cadastrado.
                </div>
            <?php else: ?>
                <?php foreach ($checklists as $c): 
                    $idC = $c['id_checklist'];
                    $perguntas = $mapaPerguntas[$idC] ?? [];
                    $status = $c['status'];

                    $badgeStatus = match($status) {
                        'pendente_aprovacao' => '<span class="badge badge-pendente px-2 py-1"><i class="bi bi-hourglass-split me-1"></i>Pendente de Validação</span>',
                        'ativo' => '<span class="badge badge-ativo px-2 py-1"><i class="bi bi-check-circle-fill me-1"></i>Ativo (Em Produção)</span>',
                        'ajuste_solicitado' => '<span class="badge badge-ajuste px-2 py-1"><i class="bi bi-exclamation-triangle-fill me-1"></i>Ajuste Solicitado</span>',
                        default => '<span class="badge bg-secondary px-2 py-1">Inativo</span>'
                    };
                ?>
                <div class="col-lg-6" id="cardChecklist_<?= $idC ?>">
                    <div class="card card-checklist h-100 bg-white p-4">
                        
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <span class="badge bg-light text-dark border small text-uppercase mb-1"><?= htmlspecialchars($c['categoria'] ?? 'Geral') ?></span>
                                <h2 class="h5 fw-bold text-dark mb-0"><?= htmlspecialchars($c['titulo']) ?></h2>
                            </div>
                            <div><?= $badgeStatus ?></div>
                        </div>

                        <p class="text-muted small mb-3"><?= htmlspecialchars($c['descricao'] ?? 'Sem descrição detalhada.') ?></p>

                        <!-- METADADOS DE AUTORIA E APROVAÇÃO -->
                        <div class="p-2 bg-light rounded small text-secondary mb-3 border">
                            <div><i class="bi bi-person me-1"></i> <strong>Criador:</strong> <?= htmlspecialchars($c['nome_criador'] ?? 'N/D') ?> (<?= ucfirst($c['perfil_criador'] ?? 'supervisor') ?>)</div>
                            <?php if ($status === 'ativo' && !empty($c['nome_aprovador'])): ?>
                                <div class="text-success mt-1"><i class="bi bi-check-all me-1"></i> <strong>Validado por:</strong> <?= htmlspecialchars($c['nome_aprovador']) ?> em <?= date('d/m/Y H:i', strtotime($c['data_aprovacao'])) ?></div>
                            <?php endif; ?>
                            <?php if ($status === 'ajuste_solicitado' && !empty($c['motivo_ajuste'])): ?>
                                <div class="text-danger mt-1"><strong><i class="bi bi-chat-left-quote me-1"></i> Parecer do Gestor:</strong> <?= htmlspecialchars($c['motivo_ajuste']) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- LISTA DE PERGUNTAS VINCULADAS -->
                        <div class="mb-3">
                            <strong class="small text-dark d-block mb-1"><i class="bi bi-list-check me-1"></i> Perguntas do Modelo (<?= count($perguntas) ?> itens):</strong>
                            <ul class="list-group list-group-flush border rounded small" style="max-height: 160px; overflow-y: auto;">
                                <?php if (empty($perguntas)): ?>
                                    <li class="list-group-item text-muted">Nenhuma pergunta associada.</li>
                                <?php else: ?>
                                    <?php foreach ($perguntas as $idx => $p): ?>
                                        <li class="list-group-item py-2 d-flex justify-content-between align-items-center">
                                            <span><strong>#<?= $idx + 1 ?></strong> <?= htmlspecialchars($p['texto_pergunta']) ?></span>
                                            <span class="badge bg-secondary-subtle text-secondary font-mono" style="font-size: 0.68rem;"><?= $p['tipo_resposta'] ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </ul>
                        </div>

                        <!-- AÇÕES DO GESTOR (SE ESTIVER PENDENTE) -->
                        <?php if ($perfilUsuario === 'gestor' && $status === 'pendente_aprovacao'): ?>
                            <div class="mt-auto pt-3 border-top d-flex gap-2 justify-content-end">
                                <button type="button" class="btn btn-outline-danger btn-sm" onclick="abrirModalAjuste(<?= $idC ?>, '<?= htmlspecialchars(addslashes($c['titulo'])) ?>')">
                                    <i class="bi bi-arrow-return-left me-1"></i> Solicitar Ajustes
                                </button>
                                <button type="button" class="btn btn-success btn-sm fw-semibold" onclick="aprovarChecklist(<?= $idC ?>)">
                                    <i class="bi bi-check2-circle me-1"></i> Validar e Ativar Modelo
                                </button>
                            </div>
                        <?php elseif ($status === 'ajuste_solicitado' && $perfilUsuario === 'supervisor'): ?>
                            <div class="mt-auto pt-3 border-top text-end">
                                <a href="editarChecklist.php?id_checklist=<?= $idC ?>" class="btn btn-warning btn-sm fw-semibold">
                                    <i class="bi bi-pencil me-1"></i> Ajustar Perguntas
                                </a>
                            </div>
                        <?php endif; ?>


                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>

    <!-- MODAL DE SOLICITAÇÃO DE AJUSTE -->
    <div class="modal fade" id="modalAjuste" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title h6 fw-bold"><i class="bi bi-chat-left-dots me-1"></i> Devolver para Ajuste</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="modalIdChecklist">
                    <p class="small text-muted mb-2">Checklist: <strong id="modalTituloChecklist" class="text-dark"></strong></p>
                    <div class="mb-3">
                        <label for="txtMotivoAjuste" class="form-label small fw-bold">Orientações de Correção para o Supervisor:</label>
                        <textarea id="txtMotivoAjuste" class="form-control" rows="4" placeholder="Ex: Adicionar pergunta sobre fluido de freio e ajustar redação do item 2..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger btn-sm fw-semibold" onclick="enviarAjuste()">
                        Enviar Devolução
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let modalAjusteInstancia = null;

        document.addEventListener('DOMContentLoaded', () => {
            const el = document.getElementById('modalAjuste');
            if (el) modalAjusteInstancia = new bootstrap.Modal(el);
        });

        async function aprovarChecklist(id) {
            if (!confirm('Deseja validar este checklist e colocá-lo em produção imediatamente para toda a frota?')) return;

            try {
                const formData = new FormData();
                formData.append('id_checklist', id);
                formData.append('acao', 'aprovar');

                const res = await fetch('../backend/processar_aprovacao_checklist.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();

                if (data.sucesso) {
                    alert(data.mensagem);
                    window.location.reload();
                } else {
                    alert('Erro: ' + (data.mensagem || 'Falha ao aprovar checklist.'));
                }
            } catch (err) {
                alert('Erro de comunicação com o servidor.');
            }
        }

        function abrirModalAjuste(id, titulo) {
            document.getElementById('modalIdChecklist').value = id;
            document.getElementById('modalTituloChecklist').innerText = titulo;
            document.getElementById('txtMotivoAjuste').value = '';
            if (modalAjusteInstancia) modalAjusteInstancia.show();
        }

        async function enviarAjuste() {
            const id = document.getElementById('modalIdChecklist').value;
            const motivo = document.getElementById('txtMotivoAjuste').value.trim();

            if (!motivo) {
                alert('Por favor, informe as orientações de ajuste para o supervisor.');
                return;
            }

            try {
                const formData = new FormData();
                formData.append('id_checklist', id);
                formData.append('acao', 'solicitar_ajuste');
                formData.append('motivo_ajuste', motivo);

                const res = await fetch('../backend/processar_aprovacao_checklist.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();

                if (data.sucesso) {
                    alert(data.mensagem);
                    window.location.reload();
                } else {
                    alert('Erro: ' + (data.mensagem || 'Falha ao solicitar ajuste.'));
                }
            } catch (err) {
                alert('Erro de comunicação com o servidor.');
            }
        }
    </script>
</body>
</html>
