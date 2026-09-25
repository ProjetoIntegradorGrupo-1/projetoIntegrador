<?php
/**
 * Listagem de Checklists - Axion Frotas
 * Versão corrigida com base na estrutura real da tabela Checklists.
 */

require_once __DIR__ . '/../auth/verificar_sessao.php';
require_once __DIR__ . '/../config/conexao.php';

exigirPerfil(['administrador', 'gestor','funcionario']);

try {
    // Recupera o ID e o perfil do utilizador autenticado na sessão atual
    $id_usuario_logado =$_SESSION['id_usuario'];
    $perfil_usuario =$_SESSION['perfil_usuario'] ?? '';

    // Se for administrador ou gestor, carrega todas as vistorias do sistema
    if ($perfil_usuario === 'administrador' || $perfil_usuario === 'gestor') {$sqlChecklists = "SELECT c.*, u.nome AS criador 
                          FROM `Checklists` c 
                          JOIN `Usuarios` u ON c.id_criador = u.id_usuario 
                          ORDER BY c.id_checklist DESC";
        $stmt = $pdo->query($sqlChecklists);
    } else {
        // Se for funcionário, carrega apenas as vistorias registadas por ele próprio
        $sqlChecklists = "SELECT c.*, u.nome AS criador 
                          FROM `Checklists` c 
                          JOIN `Usuarios` u ON c.id_criador = u.id_usuario 
                          WHERE c.id_criador = ? 
                          ORDER BY c.id_checklist DESC";
        $stmt = $pdo->prepare($sqlChecklists);
        $stmt->execute([$id_usuario_logado]);
    }

    $checklists =$stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Erro ao listar checklists: " . $e->getMessage());
    die("Erro ao carregar os dados dos checklists: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciamento de Checklists - Axion Frotas</title>
    <!-- Bootstrap 5 e FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-dark: #0f3822;
            --primary-green: #198754;
            --light-bg: #f8fafc;
            --card-radius: 16px;
        }

        body {
            background-color: var(--light-bg);
            font-family: system-ui, -apple-system, sans-serif;
            color: #1e293b;
        }

        .sidebar {
            min-height: 100vh;
            background-color: #ffffff;
            border-right: 1px solid #e2e8f0;
        }
        .sidebar .nav-link {
            color: #64748b;
            border-radius: 10px;
            margin-bottom: 6px;
            font-weight: 500;
            padding: 10px 15px;
            transition: all 0.2s;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: #ffffff;
            background-color: var(--primary-dark);
        }
        .sidebar-brand {
            font-weight: 700;
            color: var(--primary-dark);
            letter-spacing: -0.5px;
        }

        .card-custom {
            border: none;
            border-radius: var(--card-radius);
            background: #ffffff;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
        }

        .btn-green {
            background-color: var(--primary-dark);
            color: #fff;
            border-radius: 10px;
            font-weight: 500;
            border: none;
        }
        .btn-green:hover {
            background-color: #164e31;
            color: #fff;
        }
    </style>
</head>

<body>

    <div class="container-fluid">
        <div class="row">
            
            <!-- MENU LATERAL -->
            <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block sidebar collapse p-3">
                <div class="position-sticky pt-2">
                    <div class="d-flex align-items-center justify-content-between pb-3 mb-4 border-bottom">
                        <h4 class="sidebar-brand fs-5 mb-0">
                            <i class="fa-solid fa-truck-fast text-success me-2"></i>Axion Frotas
                        </h4>
                        <button class="btn btn-sm text-secondary d-md-none border-0" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu">
                            <i class="fa-solid fa-xmark fs-5"></i>
                        </button>
                    </div>
                    
                    <small class="text-uppercase text-muted fw-bold fs-7 px-2 mb-2 d-block">Menu Principal</small>
                    <ul class="nav flex-column gap-1 mb-4">
                        <li class="nav-item">
                            <a href="oqfazer.php" class="nav-link">
                                <i class="fa-solid fa-chart-pie me-2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="listausuario.php" class="nav-link">
                                <i class="fa-solid fa-users me-2"></i> Usuários
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="listaveiculo.php" class="nav-link">
                                <i class="fa-solid fa-truck me-2"></i> Veículos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="listachecklist.php" class="nav-link active">
                                <i class="fa-solid fa-clipboard-list me-2"></i> Checklists
                            </a>
                        </li>
                    </ul>

                    <small class="text-uppercase text-muted fw-bold fs-7 px-2 mb-2 d-block">Sistema</small>
                    <ul class="nav flex-column gap-1">
                        <li class="nav-item">
                            <a href="../auth/logout.php" class="nav-link text-danger">
                                <i class="fa-solid fa-right-from-bracket me-2"></i> Sair
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- CONTEÚDO PRINCIPAL -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">

                <!-- BARRA SUPERIOR -->
                <div class="d-flex justify-content-between align-items-center bg-white p-3 rounded-4 shadow-sm mb-4">
                    <div class="d-flex align-items-center gap-3">
                        <button class="btn btn-light border-0 shadow-sm" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu">
                            <i class="fa-solid fa-bars text-secondary"></i>
                        </button>
                        <div>
                            <h4 class="fw-bold text-dark mb-0">Gestão de Checklists</h4>
                            <p class="text-muted small mb-0">Consulte os modelos e formulários de checklists configurados.</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <a href="../../Frontend/cadcheck.php" class="btn btn-green btn-sm px-3 py-2 shadow-sm">
                            <i class="fa-solid fa-plus me-1"></i> Novo Checklist
                        </a>
                    </div>
                </div>

                <!-- TABELA DE CHECKLISTS -->
                <div class="card card-custom p-0 overflow-hidden shadow-sm">
                    <div class="card-header bg-white py-3 border-bottom px-4 d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold text-dark mb-0">Checklists Cadastrados</h5>
                        <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill">Total: <?= count($checklists) ?> itens</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light text-muted small text-uppercase">
                                    <tr>
                                        <th class="py-3 ps-4">ID</th>
                                        <th class="py-3">Título</th>
                                        <th class="py-3">Categoria</th>
                                        <th class="py-3">Criador</th>
                                        <th class="py-3">Data Criação</th>
                                        <th class="py-3">Estado</th>
                                        <th class="py-3 pe-4 text-end">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($checklists)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-4 text-muted">Nenhum checklist registado no momento.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($checklists as$c): ?>
                                            <tr>
                                                <td class="ps-4 fw-bold text-success">#<?= $c['id_checklist'] ?></td>
                                                <td class="fw-medium text-dark"><?= htmlspecialchars($c['titulo']) ?></td>
                                                <td><span class="badge bg-light text-dark border px-2 py-1"><?= htmlspecialchars($c['categoria']) ?></span></td>
                                                <td><?= htmlspecialchars($c['criador']) ?></td>
                                                <td class="text-muted"><?= date('d/m/Y H:i', strtotime($c['data_criacao'])) ?></td>
                                                <td>
                                                    <?php if ($c['status'] === 'ativo'): ?>
                                                        <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill">Ativo</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary bg-opacity-10 text-secondary px-3 py-2 rounded-pill">Inativo</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="pe-4 text-end">
                                                    <a href="../../checklists/processar_edicao_checklist.php?id=<?= $c['id_checklist'] ?>" class="btn btn-sm btn-light text-primary border me-1" title="Editar">
                                                        <i class="fa-solid fa-pen-to-square"></i>
                                                    </a>
                                                    <a href="../../checklists/processar_exclusao_checklist.php?id=<?= $c['id_checklist'] ?>" class="btn btn-sm btn-light text-danger border" title="Excluir" onclick="return confirm('Tem certeza que deseja apagar este checklist?');">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>