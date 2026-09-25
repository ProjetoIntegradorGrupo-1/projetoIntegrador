<?php
/**
 * ====================================================================
 * MÓDULO DE GESTÃO DE USUÁRIOS - AXION FROTAS
 * Ficheiro: listausuario.php
 * Descrição: Lista todos os operadores, gestores e administradores 
 *            cadastrados na tabela `Usuarios`, permitindo controle de perfis.
 * ====================================================================
 */

// Importa a lógica de segurança e controlo de sessão (Pertence ao subdiretório /backend/auth/)
require_once __DIR__ . '/../auth/verificar_sessao.php';

// Importa a conexão global com a base de dados via PDO (Pertence ao subdiretório /backend/config/)
require_once __DIR__ . '/../config/conexao.php';

// Restringe o acesso exclusivamente aos perfis com privilégio administrativo ou de gestão
exigirPerfil(['administrador', 'gestor']);

try {
    // Consulta SQL à tabela `Usuarios` para buscar ID, nome, e-mail e nível de acesso
    $sqlUsuarios = "SELECT id_usuario, nome, email, perfil FROM Usuarios ORDER BY id_usuario DESC";
    $usuarios = $pdo->query($sqlUsuarios)->fetchAll();
} catch (PDOException $e) {
    // Regista o erro no servidor caso a consulta falhe e interrompe a execução com mensagem amigável
    error_log("Erro ao listar usuários: " . $e->getMessage());
    die("Erro ao carregar os dados dos utilizadores.");
}
?>
<!DOCTYPE html>
<html lang="pt-pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciamento de Usuários - Axion Frotas</title>
    <!-- Importação dos estilos externos: Bootstrap 5 (UI) e FontAwesome (Ícones) -->
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

        /* Estilização da Barra Lateral (Menu de Navegação SaaS) */
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

        /* Estilização de Cartões e Componentes Visuais */
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

        /* Avatar com iniciais do utilizador para a tabela */
        .user-avatar {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background: #e6f4ed;
            color: var(--primary-green);
            font-weight: bold;
        }
    </style>
</head>

<body>

    <div class="container-fluid">
        <div class="row">
            
            <!-- ============================================== -->
            <!-- MENU LATERAL DE NAVEGAÇÃO ENTRE MÓDULOS          -->
            <!-- ============================================== -->
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
                    
                    <!-- Links de Acesso Rápido às Listagens Principais -->
                    <small class="text-uppercase text-muted fw-bold fs-7 px-2 mb-2 d-block">Menu Principal</small>
                    <ul class="nav flex-column gap-1 mb-4">
                        <li class="nav-item">
                            <a href="oqfazer.php" class="nav-link">
                                <i class="fa-solid fa-chart-pie me-2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="listausuario.php" class="nav-link active">
                                <i class="fa-solid fa-users me-2"></i> Usuários
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="listaveiculo.php" class="nav-link">
                                <i class="fa-solid fa-truck me-2"></i> Veículos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="listachecklist.php" class="nav-link">
                                <i class="fa-solid fa-clipboard-list me-2"></i> Checklists
                            </a>
                        </li>
                    </ul>

                    <!-- Controlo de Sessão do Sistema -->
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

            <!-- ============================================== -->
            <!-- CONTEÚDO PRINCIPAL DA PÁGINA                     -->
            <!-- ============================================== -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">

                <!-- BARRA SUPERIOR: Título e Botão de Ação para Novo Registo -->
                <div class="d-flex justify-content-between align-items-center bg-white p-3 rounded-4 shadow-sm mb-4">
                    <div class="d-flex align-items-center gap-3">
                        <button class="btn btn-light border-0 shadow-sm" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu">
                            <i class="fa-solid fa-bars text-secondary"></i>
                        </button>
                        <div>
                            <h4 class="fw-bold text-dark mb-0">Gestão de Usuários</h4>
                            <p class="text-muted small mb-0">Consulte e gira os operadores e gestores do sistema.</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <!-- Vincula com o formulário de cadastro correspondente localizado no Frontend -->
                        <a href="../../Frontend/caduser.php" class="btn btn-green btn-sm px-3 py-2 shadow-sm">
                            <i class="fa-solid fa-user-plus me-1"></i> Novo Usuário
                        </a>
                    </div>
                </div>

                <!-- CARTÃO / TABELA DE DADOS DOS UTILIZADORES -->
                <div class="card card-custom p-0 overflow-hidden shadow-sm">
                    <div class="card-header bg-white py-3 border-bottom px-4 d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold text-dark mb-0">Utilizadores Registados</h5>
                        <!-- Indicador dinâmico do total de registos encontrados na base de dados -->
                        <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill">Total: <?= count($usuarios) ?> utilizadores</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light text-muted small text-uppercase">
                                    <tr>
                                        <th class="py-3 ps-4">Utilizador</th>
                                        <th class="py-3">E-mail</th>
                                        <th class="py-3">Perfil</th>
                                        <th class="py-3 pe-4 text-end">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($usuarios)): ?>
                                        <!-- Mensagem exibida caso a tabela `Usuarios` esteja vazia -->
                                        <tr>
                                            <td colspan="4" class="text-center py-4 text-muted">Nenhum utilizador registado no momento.</td>
                                        </tr>
                                    <?php else: ?>
                                        <!-- Itera sobre cada registo de utilizador retornado pelo PDO -->
                                        <?php foreach ($usuarios as$u): ?>
                                            <tr>
                                                <td class="ps-4">
                                                    <div class="d-flex align-items-center gap-3">
                                                        <!-- Bloco visual com as iniciais do nome -->
                                                        <div class="user-avatar">
                                                            <?= strtoupper(substr($u['nome'], 0, 2)) ?>
                                                        </div>
                                                        <div>
                                                            <h6 class="fw-bold text-dark mb-0"><?= htmlspecialchars($u['nome']) ?></h6>
                                                            <small class="text-muted">ID: #<?= $u['id_usuario'] ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?= htmlspecialchars($u['email']) ?></td>
                                                <td>
                                                    <?php 
                                                        // Define a cor da etiqueta com base no tipo de perfil de acesso
                                                        $perfil = $u['perfil'] ?? 'operador';$badgeColor = 'bg-primary';
                                                        if ($perfil === 'administrador')$badgeColor = 'bg-danger';
                                                        if ($perfil === 'gestor')$badgeColor = 'bg-warning text-dark';
                                                    ?>
                                                    <span class="badge <?= $badgeColor ?> bg-opacity-15 px-3 py-2 rounded-pill text-uppercase" style="font-size: 0.75rem;">
                                                        <?= htmlspecialchars($perfil) ?>
                                                    </span>
                                                </td>
                                                <td class="pe-4 text-end">
                                                    <!-- Botão de Edição: Vincula com o script de alteração de dados do utilizador -->
                                                    <a href="../../usuarios/processar_edicao_usuario.php?id=<?= $u['id_usuario'] ?>" class="btn btn-sm btn-light text-primary border me-1" title="Editar dados do utilizador">
                                                        <i class="fa-solid fa-pen-to-square"></i>
                                                    </a>
                                                    <!-- Botão de Exclusão: Vincula com a rota de processamento de remoção no backend -->
                                                    <a href="../../usuarios/processar_exclusao_usuario.php?id=<?= $u['id_usuario'] ?>" class="btn btn-sm btn-light text-danger border" title="Excluir registo do utilizador" onclick="return confirm('Tem certeza que deseja apagar este utilizador do sistema?');">
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

    <!-- Scripts de suporte interativo do Bootstrap 5 -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>