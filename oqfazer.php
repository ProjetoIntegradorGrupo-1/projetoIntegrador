<?php
// oqfazer.php — Painel principal do Sistema Axion.
// Menu lateral com as áreas do sistema e um painel inicial diferente para cada perfil
// (Admin, Supervisor, Motorista e Cliente), com indicadores e gráficos.
require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/backend/auth.php';

// O nível de acesso vem do banco a cada página (não existe "simular/trocar perfil")
$eu             = axion_exigir_perfil();
$nome_usuario   = $eu['nome'];
$primeiro_nome  = explode(' ', trim($nome_usuario))[0] ?: $nome_usuario;
$perfil_usuario = $eu['perfil'];
$csrf           = axion_csrf_token();

$isAdmin      = ($perfil_usuario === 'admin');
$isSupervisor = ($perfil_usuario === 'supervisor');
$isGestor     = $isAdmin || $isSupervisor;
$isMotorista  = ($perfil_usuario === 'motorista');
$isCliente    = ($perfil_usuario === 'cliente');
$podeVistoriar = $isGestor || $isMotorista;

// Solicitações que dependem da assinatura do usuário logado
$stPend = $pdo->prepare("SELECT COUNT(*) FROM Checklist_Execucoes WHERE recusado_em IS NULL AND
    ((id_motorista = :a AND assinado_motorista_em IS NULL) OR (id_vistoriador = :b AND assinado_vistoriador_em IS NULL))");
$stPend->execute([':a' => $eu['id'], ':b' => $eu['id']]);
$minhasPendencias = (int)$stPend->fetchColumn();

// Menu de acordo com o perfil
$menu = [
    'inicio'     => ['bi-speedometer2', 'Painel'],
    'vistorias'  => ['bi-clipboard2-check', $isMotorista ? 'Minhas vistorias' : 'Vistorias'],
    'frota'      => ['bi-truck-front', 'Frota'],
];
if ($isGestor) {
    $menu['usuarios']   = ['bi-people', $isSupervisor ? 'Equipe e usuários' : 'Usuários'];
    $menu['checklists'] = ['bi-ui-checks-grid', 'Modelos de checklist'];
}
if ($isAdmin) {
    $menu['admin'] = ['bi-gear', 'Administração'];
}

// Compatibilidade com os links antigos (?aba=veiculos / ?aba=checklists)
$aba = $_GET['aba'] ?? 'inicio';
$aba = ['veiculos' => 'frota'][$aba] ?? $aba;
if (!isset($menu[$aba])) $aba = 'inicio';

$subtitulos = [
    'admin'      => 'Visão geral de todo o sistema: frota, usuários e vistorias.',
    'supervisor' => 'Acompanhe a sua equipe e aprove as vistorias que chegam para você.',
    'motorista'  => 'Faça suas vistorias e acompanhe o que ainda falta assinar.',
    'cliente'    => 'Consulta da frota e do histórico de vistorias.',
];
$hora = (int)date('H');
$saudacao = $hora < 12 ? 'Bom dia' : ($hora < 18 ? 'Boa tarde' : 'Boa noite');

$sucessoMsg = $_GET['sucesso'] ?? '';
$mapaMensagens = [
    'admin_cadastrado'      => 'Administrador cadastrado.',
    'supervisor_cadastrado' => 'Supervisor cadastrado.',
    'motorista_cadastrado'  => 'Motorista cadastrado e vinculado ao supervisor responsável.',
    'cliente_cadastrado'    => 'Cliente cadastrado.',
    'usuario_cadastrado'    => 'Usuário cadastrado.',
    'usuario_editado'       => 'Alterações do usuário salvas.',
    'usuario_excluido'      => 'Usuário excluído.',
    'veiculo_cadastrado'    => 'Veículo cadastrado na frota.',
    'veiculo_editado'       => 'Alterações do veículo salvas.',
    'veiculo_excluido'      => 'Veículo excluído da frota.',
    'checklist_criado'      => 'Modelo de checklist criado.',
    'checklist_editado'     => 'Alterações do modelo de checklist salvas.',
    'checklist_excluido'    => 'Modelo de checklist excluído.',
    'checklist_arquivado'   => 'Modelo arquivado: ele já tem vistorias registradas, por isso não foi apagado. Para usar de novo, edite e salve o modelo.',
    'usuario_desativado'    => 'Conta desativada: este usuário já participou de vistorias, por isso não foi apagado. Para reativar, edite o usuário.',
];
$erroMsg = $_GET['erro'] ?? '';
$mapaErros = [
    'sem_permissao'        => 'Seu nível de acesso não permite esta operação.',
    'formulario_expirado'  => 'O formulário expirou. Recarregue a página e tente novamente.',
    'vistoria_invalida'    => 'Esta vistoria não pode mais ser alterada.',
];
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel - Axion Gestão de Frota & Checklists</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="frontend/css/axion-web.css">
</head>

<body>
    <nav class="axion-navbar text-white">
        <div class="container-fluid px-3 px-lg-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <a href="oqfazer.php" class="axion-brand">
                <i class="bi bi-truck-front-fill fs-4"></i>
                <span>AXION</span>
                <span class="axion-brand-badge">Frota & Checklists</span>
            </a>
            <div class="d-flex align-items-center gap-2 gap-md-3">
                <?php if ($minhasPendencias > 0): ?>
                <a href="oqfazer.php?aba=vistorias&filtro=minhas_pendencias" class="btn btn-sm btn-warning fw-semibold" title="Vistorias aguardando a sua assinatura">
                    <i class="bi bi-bell-fill me-1"></i><?php echo $minhasPendencias; ?> para assinar
                </a>
                <?php endif; ?>
                <div class="d-none d-md-flex align-items-center gap-2 small border-start ps-3 border-light border-opacity-25">
                    <i class="bi bi-person-circle fs-4"></i>
                    <div class="lh-sm">
                        <div class="fw-semibold"><?php echo htmlspecialchars($nome_usuario); ?></div>
                        <div class="text-white-50"><?php echo ucfirst(htmlspecialchars($perfil_usuario)); ?></div>
                    </div>
                </div>
                <a href="logout.php" class="btn btn-sm btn-outline-light"><i class="bi bi-box-arrow-right me-1"></i>Sair</a>
            </div>
        </div>
    </nav>

    <div class="painel-layout">
        <!-- MENU LATERAL -->
        <aside class="painel-menu">
            <div class="menu-box">
                <?php if ($podeVistoriar): ?>
                <a href="cadcheck.html" class="btn btn-warning fw-semibold w-100 mb-2 menu-cta"><i class="bi bi-plus-circle me-1"></i>Nova vistoria</a>
                <?php endif; ?>
                <div class="menu-titulo">Menu</div>
                <?php foreach ($menu as $chave => [$icone, $rotulo]): ?>
                <a href="#<?php echo $chave; ?>" class="menu-link <?php echo $aba === $chave ? 'ativo' : ''; ?>" data-secao="<?php echo $chave; ?>">
                    <i class="bi <?php echo $icone; ?>"></i><span><?php echo htmlspecialchars($rotulo); ?></span>
                    <?php if ($chave === 'vistorias' && $minhasPendencias > 0): ?>
                    <span class="badge rounded-pill bg-warning text-dark"><?php echo $minhasPendencias; ?></span>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>
        </aside>

        <main class="painel-conteudo">
            <?php if ($erroMsg && isset($mapaErros[$erroMsg])): ?>
            <div class="alert alert-danger shadow-sm" role="alert"><i class="bi bi-exclamation-octagon-fill me-2"></i><?php echo htmlspecialchars($mapaErros[$erroMsg]); ?></div>
            <?php endif; ?>
            <?php if ($sucessoMsg && isset($mapaMensagens[$sucessoMsg])): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm d-flex align-items-center" role="alert">
                <i class="bi bi-check-circle-fill fs-5 me-2"></i>
                <div><?php echo htmlspecialchars($mapaMensagens[$sucessoMsg]); ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
            </div>
            <?php endif; ?>

            <!-- ===================== PAINEL INICIAL ===================== -->
            <section class="secao" id="secao-inicio">
                <div class="secao-topo">
                    <div>
                        <h1><?php echo $saudacao . ', ' . htmlspecialchars($primeiro_nome); ?></h1>
                        <p><?php echo $subtitulos[$perfil_usuario]; ?></p>
                    </div>
                    <!-- Atalhos do perfil -->
                    <div class="d-flex flex-wrap gap-2">
                        <?php if ($isGestor): ?>
                        <a href="caduser.html" class="btn btn-outline-primary btn-sm"><i class="bi bi-person-plus me-1"></i><?php echo $isSupervisor ? 'Cadastrar motorista' : 'Cadastrar usuário'; ?></a>
                        <a href="cadcar.html" class="btn btn-outline-primary btn-sm"><i class="bi bi-truck me-1"></i>Cadastrar veículo</a>
                        <a href="criarChecklist.html" class="btn btn-outline-primary btn-sm"><i class="bi bi-ui-checks me-1"></i>Criar checklist</a>
                        <?php endif; ?>
                        <?php if ($podeVistoriar): ?>
                        <a href="cadcheck.html" class="btn btn-warning btn-sm fw-semibold"><i class="bi bi-plus-circle me-1"></i>Nova vistoria</a>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($minhasPendencias > 0): ?>
                <div class="card border-warning shadow-sm mb-3">
                    <div class="card-header bg-warning-subtle fw-semibold">
                        <i class="bi bi-bell-fill text-warning-emphasis me-1"></i>
                        <?php echo $isSupervisor || $isAdmin ? 'Vistorias aguardando a sua aprovação' : 'Vistorias aguardando a sua assinatura'; ?> (<?php echo $minhasPendencias; ?>)
                    </div>
                    <div class="list-group list-group-flush" id="lista-solicitacoes">
                        <div class="list-group-item text-muted small">Carregando...</div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Indicadores (preenchidos pelo JS conforme o perfil) -->
                <div class="row g-3 mb-3" id="kpis"></div>

                <!-- Gráficos e quadros do perfil -->
                <div class="row g-3" id="graficos">
                    <?php if ($isAdmin): ?>
                    <div class="col-xl-8"><div class="cartao"><h3>Vistorias nos últimos 14 dias</h3><div class="cartao-sub">Quantidade por dia, separando conformes e não conformes</div><div class="grafico"><canvas id="g-dias"></canvas></div></div></div>
                    <div class="col-xl-4"><div class="cartao"><h3>Frota por situação</h3><div class="cartao-sub">Veículos cadastrados</div><div class="grafico"><canvas id="g-frota"></canvas></div></div></div>
                    <div class="col-xl-4"><div class="cartao"><h3>Usuários ativos por perfil</h3><div class="cartao-sub" id="sub-usuarios">Contas que podem entrar no sistema</div><div class="grafico"><canvas id="g-usuarios"></canvas></div></div></div>
                    <div class="col-xl-4"><div class="cartao"><h3>Vistorias por motorista</h3><div class="cartao-sub">Últimos 30 dias</div><div class="grafico"><canvas id="g-motoristas"></canvas></div></div></div>
                    <div class="col-xl-4"><div class="cartao"><h3>Veículos com mais avarias</h3><div class="cartao-sub">Vistorias não conformes nos últimos 90 dias</div><div class="grafico"><canvas id="g-avarias"></canvas></div></div></div>
                    <?php elseif ($isSupervisor): ?>
                    <div class="col-xl-7"><div class="cartao"><h3>Vistorias da sua equipe (14 dias)</h3><div class="cartao-sub">Quantidade por dia, separando conformes e não conformes</div><div class="grafico"><canvas id="g-dias"></canvas></div></div></div>
                    <div class="col-xl-5"><div class="cartao"><h3>Vistorias por motorista</h3><div class="cartao-sub">Sua equipe nos últimos 30 dias</div><div class="grafico"><canvas id="g-motoristas"></canvas></div></div></div>
                    <div class="col-xl-7"><div class="cartao">
                        <h3>Sua equipe</h3><div class="cartao-sub">Motoristas vinculados a você: você aprova as vistorias deles</div>
                        <div class="table-responsive"><table class="table table-sm align-middle mb-0">
                            <thead><tr><th>Motorista</th><th>CNH</th><th>Última vistoria</th></tr></thead>
                            <tbody id="tbody-equipe"><tr><td colspan="3" class="text-muted small">Carregando...</td></tr></tbody>
                        </table></div>
                    </div></div>
                    <div class="col-xl-5"><div class="cartao"><h3>Veículos com mais avarias</h3><div class="cartao-sub">Vistorias não conformes da equipe (90 dias)</div><div class="grafico"><canvas id="g-avarias"></canvas></div></div></div>
                    <?php elseif ($isMotorista): ?>
                    <div class="col-xl-7"><div class="cartao"><h3>Suas vistorias nos últimos 14 dias</h3><div class="cartao-sub">Conformes e não conformes por dia</div><div class="grafico"><canvas id="g-dias"></canvas></div></div></div>
                    <div class="col-xl-5"><div class="cartao">
                        <h3>Últimas vistorias</h3><div class="cartao-sub">Clique para ver os detalhes</div>
                        <div class="list-group list-group-flush" id="ultimas-vistorias"><div class="text-muted small">Carregando...</div></div>
                    </div></div>
                    <?php else: ?>
                    <div class="col-xl-8"><div class="cartao"><h3>Vistorias nos últimos 14 dias</h3><div class="cartao-sub">Quantidade por dia, separando conformes e não conformes</div><div class="grafico"><canvas id="g-dias"></canvas></div></div></div>
                    <div class="col-xl-4"><div class="cartao"><h3>Frota por situação</h3><div class="cartao-sub">Veículos cadastrados</div><div class="grafico"><canvas id="g-frota"></canvas></div></div></div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- ===================== VISTORIAS ===================== -->
            <section class="secao" id="secao-vistorias">
                <div class="secao-topo">
                    <div>
                        <h1><?php echo $isMotorista ? 'Minhas vistorias' : 'Vistorias'; ?></h1>
                        <p>Checklists preenchidos, resultado e situação das assinaturas.</p>
                    </div>
                    <?php if ($podeVistoriar): ?>
                    <a href="cadcheck.html" class="btn btn-warning fw-semibold"><i class="bi bi-plus-circle me-1"></i>Nova vistoria</a>
                    <?php endif; ?>
                </div>
                <div class="cartao mb-3">
                    <div class="row g-2">
                        <div class="col-md-7">
                            <input type="search" id="busca-vistorias-q" class="form-control" placeholder="Buscar por placa, motorista, vistoriador ou checklist..." oninput="buscarComAtraso('vistorias')">
                        </div>
                        <div class="col-md-5">
                            <select id="busca-vistorias-status" class="form-select" onchange="carregarBusca('vistorias')">
                                <option value="">Todas</option>
                                <?php if (!$isCliente): ?><option value="minhas_pendencias">Aguardando a minha assinatura</option><?php endif; ?>
                                <option value="pendente">Aguardando assinatura</option>
                                <option value="conforme">Conformes</option>
                                <option value="nao_conforme">Não conformes</option>
                                <option value="recusada">Recusadas</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="axion-table-wrapper">
                    <div class="table-responsive">
                        <table class="table table-hover axion-table mb-0">
                            <thead><tr><th>#</th><th>Data</th><th>Placa</th><th>Checklist</th><th>Motorista</th><th>Vistoriador</th><th>Resultado</th><th class="text-end">Ações</th></tr></thead>
                            <tbody id="tbody-vistorias"><tr><td colspan="8" class="text-center py-4 text-muted">Carregando...</td></tr></tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- ===================== FROTA ===================== -->
            <section class="secao" id="secao-frota">
                <div class="secao-topo">
                    <div><h1>Frota</h1><p>Veículos cadastrados e situação atual.</p></div>
                    <?php if ($isGestor): ?>
                    <a href="cadcar.html" class="btn btn-success"><i class="bi bi-plus-lg me-1"></i>Cadastrar veículo</a>
                    <?php endif; ?>
                </div>
                <div class="cartao mb-3">
                    <div class="row g-2">
                        <div class="col-md-8"><input type="search" id="busca-veiculos-q" class="form-control" placeholder="Buscar por placa, marca, modelo, cor, Renavam ou chassi..." oninput="buscarComAtraso('veiculos')"></div>
                        <div class="col-md-4">
                            <select id="busca-veiculos-status" class="form-select" onchange="carregarBusca('veiculos')">
                                <option value="">Todas as situações</option>
                                <option value="ativo">Ativo</option>
                                <option value="em_rota">Em rota</option>
                                <option value="manutencao">Em manutenção</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="axion-table-wrapper">
                    <div class="table-responsive">
                        <table class="table table-hover axion-table mb-0">
                            <thead><tr><th>Placa</th><th>Veículo</th><th>Ano</th><th>Cor</th><th>Km</th><th>Situação</th><th class="text-end">Ações</th></tr></thead>
                            <tbody id="tbody-veiculos"><tr><td colspan="7" class="text-center py-4 text-muted">Carregando...</td></tr></tbody>
                        </table>
                    </div>
                </div>
            </section>

            <?php if ($isGestor): ?>
            <!-- ===================== USUÁRIOS ===================== -->
            <section class="secao" id="secao-usuarios">
                <div class="secao-topo">
                    <div>
                        <h1><?php echo $isSupervisor ? 'Equipe e usuários' : 'Usuários'; ?></h1>
                        <p><?php echo $isSupervisor ? 'Você gerencia motoristas e clientes. Filtre por "Minha equipe" para ver só os seus motoristas.' : 'Todas as contas do sistema.'; ?></p>
                    </div>
                    <a href="caduser.html" class="btn btn-success"><i class="bi bi-person-plus me-1"></i>Cadastrar usuário</a>
                </div>
                <div class="cartao mb-3">
                    <div class="row g-2">
                        <div class="col-md-7"><input type="search" id="busca-usuarios-q" class="form-control" placeholder="Buscar por nome, CPF (com ou sem pontos), e-mail ou cidade..." oninput="buscarComAtraso('usuarios')"></div>
                        <div class="col-md-5">
                            <select id="busca-usuarios-perfil" class="form-select" onchange="carregarBusca('usuarios')">
                                <option value="">Todos os perfis</option>
                                <?php if ($isSupervisor): ?><option value="minha_equipe" selected>Minha equipe</option><?php endif; ?>
                                <option value="motorista">Motoristas</option>
                                <option value="cliente">Clientes</option>
                                <option value="supervisor">Supervisores</option>
                                <option value="admin">Administradores</option>
                                <option value="inativo">Contas desativadas</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="axion-table-wrapper">
                    <div class="table-responsive">
                        <table class="table table-hover axion-table mb-0">
                            <thead><tr><th>Nome</th><th>CPF</th><th>Perfil</th><th>CNH</th><th>Contato</th><th class="text-end">Ações</th></tr></thead>
                            <tbody id="tbody-usuarios"><tr><td colspan="6" class="text-center py-4 text-muted">Carregando...</td></tr></tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- ===================== MODELOS DE CHECKLIST ===================== -->
            <section class="secao" id="secao-checklists">
                <div class="secao-topo">
                    <div><h1>Modelos de checklist</h1><p>Os formulários usados nas vistorias.</p></div>
                    <a href="criarChecklist.html" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Criar modelo</a>
                </div>
                <div class="cartao mb-3">
                    <div class="row g-2">
                        <div class="col-md-8"><input type="search" id="busca-checklists-q" class="form-control" placeholder="Buscar por título ou descrição..." oninput="buscarComAtraso('checklists')"></div>
                        <div class="col-md-4">
                            <select id="busca-checklists-cat" class="form-select" onchange="carregarBusca('checklists')">
                                <option value="">Todas as categorias</option>
                                <option value="pre_viagem">Pré-viagem</option>
                                <option value="mecanico">Mecânico</option>
                                <option value="eletrico">Elétrico</option>
                                <option value="seguranca">Segurança</option>
                                <option value="estetico">Estético</option>
                                <option value="outros">Outros</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="axion-table-wrapper">
                    <div class="table-responsive">
                        <table class="table table-hover axion-table mb-0">
                            <thead><tr><th>Modelo</th><th>Categoria</th><th>Perguntas</th><th>Vistorias</th><th>Criado por</th><th class="text-end">Ações</th></tr></thead>
                            <tbody id="tbody-checklists"><tr><td colspan="6" class="text-center py-4 text-muted">Carregando...</td></tr></tbody>
                        </table>
                    </div>
                </div>
            </section>
            <?php endif; ?>

            <?php if ($isAdmin): ?>
            <!-- ===================== ADMINISTRAÇÃO ===================== -->
            <section class="secao" id="secao-admin">
                <div class="secao-topo"><div><h1>Administração</h1><p>Manutenção da base de dados.</p></div></div>
                <div class="row g-3">
                    <div class="col-lg-6">
                        <div class="cartao">
                            <h3 class="text-danger"><i class="bi bi-trash3 me-1"></i>Zerar banco de dados</h3>
                            <p class="small text-muted">Apaga todos os carros, usuários, checklists e vistorias. Ficam só as 4 contas de acesso
                                (admin, supervisor, motorista e cliente @axion.com, senha <code>mudar123</code>). Não dá para desfazer.</p>
                            <form action="database/seed.php" method="post" onsubmit="return confirm('Apagar TODOS os dados do sistema? Esta ação não pode ser desfeita.');">
                                <input type="hidden" name="acao" value="zerar">
                                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
                                <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash3 me-1"></i>Zerar banco agora</button>
                            </form>
                        </div>
                    </div>
                </div>
            </section>
            <?php endif; ?>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        const PERFIL = <?php echo json_encode($perfil_usuario); ?>;
        const IS_GESTOR = <?php echo $isGestor ? 'true' : 'false'; ?>;
        const IS_ADMIN = <?php echo $isAdmin ? 'true' : 'false'; ?>;
        const PODE_VISTORIAR = <?php echo $podeVistoriar ? 'true' : 'false'; ?>;
        const MEU_ID = <?php echo (int)$eu['id']; ?>;
        const ABA_INICIAL = <?php echo json_encode($aba); ?>;
        const FILTRO_INICIAL = new URLSearchParams(location.search).get('filtro') || '';

        // Escapa textos do banco antes de colocar no HTML
        const esc = (v) => String(v ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        const dataBR = (d) => d ? d.slice(0, 10).split('-').reverse().join('/') : '-';
        const getJson = async (url) => (await fetch(url, { headers: { Accept: 'application/json' } })).json();

        // ---------- Navegação entre as áreas do menu ----------
        const carregadas = {};
        function abrirSecao(nome) {
            if (!document.getElementById('secao-' + nome)) nome = 'inicio';
            document.querySelectorAll('.secao').forEach(s => s.classList.toggle('ativa', s.id === 'secao-' + nome));
            document.querySelectorAll('.menu-link').forEach(a => a.classList.toggle('ativo', a.dataset.secao === nome));
            const url = new URL(location.href);
            url.searchParams.set('aba', nome);
            ['sucesso', 'erro'].forEach(p => url.searchParams.delete(p));
            history.replaceState(null, '', url);
            if (!carregadas[nome]) {
                carregadas[nome] = true;
                const tipo = { vistorias: 'vistorias', frota: 'veiculos', usuarios: 'usuarios', checklists: 'checklists' }[nome];
                if (tipo) carregarBusca(tipo);
            }
            window.scrollTo({ top: 0 });
        }
        document.querySelectorAll('[data-secao]').forEach(a => a.addEventListener('click', (ev) => { ev.preventDefault(); abrirSecao(a.dataset.secao); }));

        // ---------- Etiquetas ----------
        function badgeStatusVeiculo(st) {
            if (st === 'ativo') return '<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check-circle me-1"></i>Ativo</span>';
            if (st === 'em_rota') return '<span class="badge bg-primary-subtle text-primary border border-primary-subtle"><i class="bi bi-signpost-2 me-1"></i>Em rota</span>';
            if (st === 'manutencao') return '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle"><i class="bi bi-tools me-1"></i>Manutenção</span>';
            return `<span class="badge bg-secondary">${esc(st)}</span>`;
        }
        function badgePerfil(p) {
            return { admin: '<span class="badge bg-dark">Admin</span>', supervisor: '<span class="badge bg-warning text-dark">Supervisor</span>', motorista: '<span class="badge bg-primary">Motorista</span>' }[p] || '<span class="badge bg-info text-dark">Cliente</span>';
        }
        function badgeConformidade(st) {
            return st === 'nao_conforme' ? '<span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Não conforme</span>' : '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Conforme</span>';
        }
        function badgeSituacao(sit) {
            return {
                concluida: '<span class="badge bg-success-subtle text-success">Concluída</span>',
                aguardando_vistoriador: '<span class="badge bg-warning text-dark">Aguardando supervisor</span>',
                aguardando_motorista: '<span class="badge bg-warning text-dark">Aguardando motorista</span>',
                aguardando_assinaturas: '<span class="badge bg-secondary">Não assinada</span>',
                recusada: '<span class="badge bg-dark">Recusada</span>'
            }[sit] || '';
        }

        // ---------- Tabelas de busca ----------
        const timers = {};
        function buscarComAtraso(tipo) { clearTimeout(timers[tipo]); timers[tipo] = setTimeout(() => carregarBusca(tipo), 250); }

        async function carregarBusca(tipo) {
            const val = (id) => document.getElementById(id)?.value || '';
            const cfg = {
                vistorias: ['tbody-vistorias', 8, val('busca-vistorias-q'), val('busca-vistorias-status')],
                veiculos: ['tbody-veiculos', 7, val('busca-veiculos-q'), val('busca-veiculos-status')],
                usuarios: ['tbody-usuarios', 6, val('busca-usuarios-q'), val('busca-usuarios-perfil')],
                checklists: ['tbody-checklists', 6, val('busca-checklists-q'), val('busca-checklists-cat')],
            }[tipo];
            const tbody = document.getElementById(cfg[0]);
            if (!tbody) return;
            try {
                const json = await getJson(`backend/api.php?acao=buscar&tipo=${tipo}&q=${encodeURIComponent(cfg[2])}&filtro=${encodeURIComponent(cfg[3])}&limite=250`);
                const dados = json.dados || [];
                if (!dados.length) {
                    tbody.innerHTML = `<tr><td colspan="${cfg[1]}" class="text-center py-4 text-muted">Nada encontrado.</td></tr>`;
                    return;
                }
                tbody.innerHTML = dados.map(linhas[tipo]).join('');
            } catch (e) {
                tbody.innerHTML = `<tr><td colspan="${cfg[1]}" class="text-center py-4 text-danger">Não foi possível carregar.</td></tr>`;
            }
        }

        const linhas = {
            vistorias: (v) => `
                <tr>
                    <td class="text-muted small">#${v.id_execucao}</td>
                    <td class="small">${dataBR(v.data_vistoria)} <span class="text-muted">${esc((v.hora_vistoria || '').slice(0, 5))}</span></td>
                    <td><span class="badge bg-dark font-monospace">${esc(v.placa_veiculo)}</span></td>
                    <td class="small fw-semibold">${esc(v.checklist_titulo)}</td>
                    <td class="small">${esc(v.nome_motorista)}</td>
                    <td class="small text-muted">${esc(v.nome_vistoriador)}</td>
                    <td>${badgeConformidade(v.status_conformidade)}<div class="mt-1">${badgeSituacao(v.situacao)}</div></td>
                    <td class="text-end text-nowrap">
                        ${v.minha_vez ? `<a href="verVistoria.html?id=${v.id_execucao}" class="btn btn-sm btn-warning fw-semibold"><i class="bi bi-pen"></i> ${Number(v.id_vistoriador) === MEU_ID ? 'Aprovar' : 'Assinar'}</a>` : ''}
                        <a href="verVistoria.html?id=${v.id_execucao}" class="btn btn-sm btn-outline-secondary" title="Ver vistoria"><i class="bi bi-eye"></i> Ver</a>
                    </td>
                </tr>`,
            veiculos: (v) => `
                <tr>
                    <td><span class="badge bg-dark font-monospace fs-6">${esc(v.placa)}</span></td>
                    <td class="fw-semibold">${esc(v.marca_modelo)}</td>
                    <td>${esc(v.ano)}</td>
                    <td>${esc(v.cor || '-')}</td>
                    <td>${Number(v.km_rodado || 0).toLocaleString('pt-BR')} km</td>
                    <td>${badgeStatusVeiculo(v.status)}</td>
                    <td class="text-end text-nowrap">
                        ${PODE_VISTORIAR ? `<a href="cadcheck.html?placa=${encodeURIComponent(v.placa)}" class="btn btn-sm btn-warning" title="Fazer vistoria deste veículo"><i class="bi bi-clipboard2-plus"></i></a>` : ''}
                        ${IS_GESTOR ? `<a href="editarVeiculo.html?id=${v.id_veiculo}" class="btn btn-sm btn-outline-primary" title="Editar"><i class="bi bi-pencil"></i></a>
                        <a href="excluirVeiculo.html?id=${v.id_veiculo}" class="btn btn-sm btn-outline-danger" title="Excluir"><i class="bi bi-trash"></i></a>` : ''}
                    </td>
                </tr>`,
            usuarios: (u) => `
                <tr class="${u.status === 'inativo' ? 'text-muted' : ''}">
                    <td>
                        <div class="fw-semibold">${esc(u.nome)} ${u.status === 'inativo' ? '<span class="badge bg-secondary">Desativado</span>' : ''}</div>
                        ${u.perfil === 'motorista' ? `<div class="small text-muted">Supervisor: ${esc(u.supervisor_nome || 'não definido')}</div>` : ''}
                    </td>
                    <td class="font-monospace small">${esc(u.cpf_matricula)}</td>
                    <td>${badgePerfil(u.perfil)}</td>
                    <td class="small">${u.cnh ? `${esc(u.categoria_cnh || '-')} · vence ${dataBR(u.validade_cnh)}` : '-'}</td>
                    <td class="small"><div>${esc(u.email)}</div><div class="text-muted">${esc(u.telefone || '')}</div></td>
                    <td class="text-end text-nowrap">
                        ${(IS_ADMIN || !['admin', 'supervisor'].includes(u.perfil)) ? `
                        <a href="editarUsuario.html?id=${u.id_usuario}" class="btn btn-sm btn-outline-primary" title="Editar"><i class="bi bi-pencil"></i></a>
                        ${Number(u.id_usuario) !== MEU_ID ? `<a href="excluirUsuario.html?id=${u.id_usuario}" class="btn btn-sm btn-outline-danger" title="Excluir"><i class="bi bi-trash"></i></a>` : ''}` : ''}
                    </td>
                </tr>`,
            checklists: (c) => `
                <tr>
                    <td>
                        <div class="fw-semibold">${esc(c.titulo)} ${Number(c.ativo) === 1 ? '' : '<span class="badge bg-secondary">Arquivado</span>'}</div>
                        <div class="small text-muted">${esc(c.descricao || '')}</div>
                    </td>
                    <td><span class="badge bg-primary-subtle text-primary">${esc(c.categoria)}</span></td>
                    <td>${c.total_perguntas}</td>
                    <td>${c.total_execucoes}</td>
                    <td class="small">${esc(c.criador_nome)}</td>
                    <td class="text-end text-nowrap">
                        ${Number(c.ativo) === 1 ? `<a href="cadcheck.html?id_checklist=${c.id_checklist}" class="btn btn-sm btn-warning" title="Usar em uma vistoria"><i class="bi bi-clipboard2-plus"></i></a>` : ''}
                        <a href="editarChecklist.html?id=${c.id_checklist}" class="btn btn-sm btn-outline-primary" title="Editar"><i class="bi bi-pencil"></i></a>
                        <a href="excluirChecklist.html?id=${c.id_checklist}" class="btn btn-sm btn-outline-danger" title="Excluir"><i class="bi bi-trash"></i></a>
                    </td>
                </tr>`
        };

        // ---------- Painel inicial: indicadores e gráficos ----------
        const COR = { conforme: '#0ca30c', nao: '#d03b3b', aviso: '#fab219', s1: '#2a78d6', s2: '#eb6834', s3: '#1baf7a', s4: '#6250d6', texto: '#52514e', grade: '#e8ebef' };
        if (window.Chart) {
            Chart.defaults.font.family = "'Segoe UI', system-ui, sans-serif";
            Chart.defaults.color = COR.texto;
            Chart.defaults.plugins.legend.labels.boxWidth = 12;
            Chart.defaults.plugins.legend.labels.boxHeight = 12;
        }
        const eixos = (empilhado, horizontal) => ({
            x: { stacked: empilhado, grid: { display: !!horizontal, color: COR.grade }, ticks: { precision: 0 }, beginAtZero: true },
            y: { stacked: empilhado, grid: { display: !horizontal, color: COR.grade }, ticks: { precision: 0 }, beginAtZero: true }
        });
        const barra = (cor) => ({ backgroundColor: cor, borderRadius: 4, borderSkipped: 'start', maxBarThickness: 34 });

        function grafico(id, config, vazio) {
            const el = document.getElementById(id);
            if (!el) return;
            if (vazio || !window.Chart) {
                el.parentElement.outerHTML = `<div class="grafico-vazio">${window.Chart ? 'Ainda não há dados para este gráfico.' : 'Gráfico indisponível sem internet.'}</div>`;
                return;
            }
            new Chart(el, { ...config, options: { responsive: true, maintainAspectRatio: false, ...config.options } });
        }

        function kpi(icone, rotulo, valor, detalhe, cor) {
            return `<div class="col-6 col-xl-3"><div class="kpi">
                <div class="kpi-rotulo"><i class="bi ${icone}" style="color:${cor || 'var(--axion-primary)'}"></i>${rotulo}</div>
                <div class="kpi-valor">${valor}</div>
                <div class="kpi-detalhe">${detalhe || '&nbsp;'}</div>
            </div></div>`;
        }

        async function carregarPainel() {
            const json = await getJson('backend/api.php?acao=dashboard');
            if (!json.sucesso) return;
            const d = json.dados;
            const v = d.vistorias;
            const taxa = v.total ? Math.round((v.conformes / v.total) * 100) + '%' : '-';
            const frotaTotal = d.frota.ativo + d.frota.em_rota + d.frota.manutencao;
            let cards = [];
            if (PERFIL === 'admin') {
                const u = d.usuarios || {};
                const totalUsu = Object.values(u).reduce((a, b) => a + Number(b), 0);
                cards = [
                    kpi('bi-truck-front', 'Veículos na frota', frotaTotal, `${d.frota.manutencao} em manutenção`),
                    kpi('bi-people', 'Usuários ativos', totalUsu, `${u.motorista || 0} motoristas · ${u.supervisor || 0} supervisores`),
                    kpi('bi-clipboard2-check', 'Vistorias (30 dias)', v.ultimos30, `${v.total} no total`),
                    kpi('bi-shield-check', 'Taxa de conformidade', taxa, `${v.nao_conformes} não conformes no total`, COR.conforme),
                ];
            } else if (PERFIL === 'supervisor') {
                cards = [
                    kpi('bi-bell', 'Aguardando sua aprovação', d.minhas_pendencias, d.minhas_pendencias ? 'Veja a lista acima' : 'Nada pendente', '#b07c00'),
                    kpi('bi-people', 'Motoristas na equipe', (d.equipe || []).length, 'Vinculados a você'),
                    kpi('bi-clipboard2-check', 'Vistorias da equipe (30 dias)', v.ultimos30, `${v.recusadas} recusadas no total`),
                    kpi('bi-shield-check', 'Conformidade da equipe', taxa, `${v.nao_conformes} não conformes`, COR.conforme),
                ];
            } else if (PERFIL === 'motorista') {
                const cnh = d.cnh || {};
                const venc = cnh.validade_cnh;
                const diasCnh = venc ? Math.ceil((new Date(venc) - new Date()) / 86400000) : null;
                cards = [
                    kpi('bi-clipboard2-check', 'Minhas vistorias', v.total, v.ultima ? `Última em ${dataBR(v.ultima)}` : 'Nenhuma ainda'),
                    kpi('bi-hourglass-split', 'Aguardando supervisor', v.aguardando, 'Já assinadas por você ou pendentes', '#b07c00'),
                    kpi('bi-x-octagon', 'Recusadas', v.recusadas, v.recusadas ? 'Veja o motivo e refaça' : 'Nenhuma', COR.nao),
                    kpi('bi-person-vcard', 'Minha CNH', venc ? dataBR(venc) : '-', diasCnh === null ? 'Validade não informada' : diasCnh < 0 ? '<span class="text-danger fw-semibold">Vencida</span>' : diasCnh <= 30 ? `<span class="text-warning-emphasis fw-semibold">Vence em ${diasCnh} dias</span>` : `Categoria ${esc(cnh.categoria_cnh || '-')}`),
                ];
            } else {
                cards = [
                    kpi('bi-truck-front', 'Veículos na frota', frotaTotal, `${d.frota.ativo} ativos`),
                    kpi('bi-tools', 'Em manutenção', d.frota.manutencao, 'Fora de operação', '#b07c00'),
                    kpi('bi-clipboard2-check', 'Vistorias (30 dias)', v.ultimos30, `${v.total} no total`),
                    kpi('bi-shield-check', 'Taxa de conformidade', taxa, `${v.nao_conformes} não conformes`, COR.conforme),
                ];
            }
            document.getElementById('kpis').innerHTML = cards.join('');

            // Vistorias por dia (conforme x não conforme)
            const dias = d.por_dia;
            grafico('g-dias', {
                type: 'bar',
                data: {
                    labels: dias.map(x => x.dia.slice(8, 10) + '/' + x.dia.slice(5, 7)),
                    datasets: [
                        { label: 'Conformes', data: dias.map(x => x.conforme), ...barra(COR.conforme) },
                        { label: 'Não conformes', data: dias.map(x => x.nao_conforme), ...barra(COR.nao) }
                    ]
                },
                options: { scales: eixos(true), plugins: { legend: { position: 'top', align: 'end' } } }
            }, !dias.some(x => x.conforme + x.nao_conforme > 0));

            // Frota por situação
            grafico('g-frota', {
                type: 'bar',
                data: {
                    labels: ['Ativo', 'Em rota', 'Manutenção'],
                    datasets: [{ label: 'Veículos', data: [d.frota.ativo, d.frota.em_rota, d.frota.manutencao], ...barra([COR.s1, COR.s3, COR.aviso]) }]
                },
                options: { indexAxis: 'y', scales: eixos(false, true), plugins: { legend: { display: false } } }
            }, frotaTotal === 0);

            // Usuários por perfil (admin)
            if (d.usuarios) {
                const ordem = [['admin', 'Admin'], ['supervisor', 'Supervisores'], ['motorista', 'Motoristas'], ['cliente', 'Clientes']];
                if (d.usuarios_inativos) document.getElementById('sub-usuarios').textContent = `Contas ativas (${d.usuarios_inativos} desativadas não entram na conta)`;
                grafico('g-usuarios', {
                    type: 'bar',
                    data: { labels: ordem.map(o => o[1]), datasets: [{ label: 'Usuários', data: ordem.map(o => Number(d.usuarios[o[0]] || 0)), ...barra(COR.s1) }] },
                    options: { indexAxis: 'y', scales: eixos(false, true), plugins: { legend: { display: false } } }
                });
            }

            // Vistorias por motorista (gestores)
            if (d.por_motorista) {
                const pm = d.por_motorista;
                grafico('g-motoristas', {
                    type: 'bar',
                    data: {
                        labels: pm.map(x => x.nome),
                        datasets: [
                            { label: 'Conformes', data: pm.map(x => Number(x.conforme)), ...barra(COR.conforme) },
                            { label: 'Não conformes', data: pm.map(x => Number(x.nao_conforme)), ...barra(COR.nao) }
                        ]
                    },
                    options: { indexAxis: 'y', scales: eixos(true, true), plugins: { legend: { position: 'top', align: 'end' } } }
                }, pm.length === 0);
            }

            // Veículos com mais avarias
            if (d.top_avarias) {
                const ta = d.top_avarias;
                grafico('g-avarias', {
                    type: 'bar',
                    data: { labels: ta.map(x => x.placa), datasets: [{ label: 'Não conformes', data: ta.map(x => Number(x.qtd)), ...barra(COR.nao) }] },
                    options: { indexAxis: 'y', scales: eixos(false, true), plugins: { legend: { display: false } } }
                }, ta.length === 0);
            }

            // Equipe do supervisor
            if (d.equipe) {
                const hoje = new Date().toISOString().slice(0, 10);
                document.getElementById('tbody-equipe').innerHTML = d.equipe.length ? d.equipe.map(m => {
                    const vencida = m.validade_cnh && m.validade_cnh < hoje;
                    return `<tr>
                        <td class="fw-semibold">${esc(m.nome)}</td>
                        <td class="small">${m.validade_cnh ? (vencida ? `<span class="badge bg-danger"><i class="bi bi-exclamation-triangle me-1"></i>Vencida ${dataBR(m.validade_cnh)}</span>` : `${esc(m.categoria_cnh || '')} · ${dataBR(m.validade_cnh)}`) : '-'}</td>
                        <td class="small">${m.ultima_vistoria ? dataBR(m.ultima_vistoria) : '<span class="text-muted">Nenhuma</span>'}</td>
                    </tr>`;
                }).join('') : '<tr><td colspan="3" class="text-muted small">Nenhum motorista vinculado a você ainda.</td></tr>';
            }
        }

        async function carregarSolicitacoes() {
            const box = document.getElementById('lista-solicitacoes');
            if (!box) return;
            const json = await getJson('backend/api.php?acao=buscar&tipo=vistorias&filtro=minhas_pendencias&limite=50');
            const dados = json.dados || [];
            box.innerHTML = dados.length ? dados.map(v => {
                const souVist = Number(v.id_vistoriador) === MEU_ID;
                const outro = souVist ? v.nome_motorista : v.nome_vistoriador;
                const outroAssinou = souVist ? v.assinado_motorista_em : v.assinado_vistoriador_em;
                return `<a href="verVistoria.html?id=${v.id_execucao}" class="list-group-item list-group-item-action d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <div class="fw-semibold">${esc(v.placa_veiculo)} · ${esc(v.checklist_titulo)} ${badgeConformidade(v.status_conformidade)}</div>
                        <div class="small text-muted">${dataBR(v.data_vistoria)} · ${outroAssinou ? `${esc(outro)} assinou e enviou para você` : 'Falta a sua assinatura para enviar'}</div>
                    </div>
                    <span class="btn btn-sm btn-warning fw-semibold"><i class="bi bi-pen me-1"></i>${souVist && outroAssinou ? 'Revisar e aprovar' : 'Assinar'}</span>
                </a>`;
            }).join('') : '<div class="list-group-item text-muted small">Nenhuma pendência.</div>';
        }

        async function carregarUltimasVistorias() {
            const box = document.getElementById('ultimas-vistorias');
            if (!box) return;
            const json = await getJson('backend/api.php?acao=buscar&tipo=vistorias&limite=10');
            const dados = (json.dados || []).slice(0, 5);
            box.innerHTML = dados.length ? dados.map(v => `
                <a href="verVistoria.html?id=${v.id_execucao}" class="list-group-item list-group-item-action px-0">
                    <div class="d-flex justify-content-between gap-2">
                        <span class="fw-semibold">${esc(v.placa_veiculo)} · ${esc(v.checklist_titulo)}</span>
                        <span class="small text-muted text-nowrap">${dataBR(v.data_vistoria)}</span>
                    </div>
                    <div class="mt-1">${badgeConformidade(v.status_conformidade)} ${badgeSituacao(v.situacao)}</div>
                </a>`).join('') : '<div class="text-muted small">Você ainda não fez nenhuma vistoria.</div>';
        }

        document.addEventListener('DOMContentLoaded', () => {
            if (FILTRO_INICIAL && document.getElementById('busca-vistorias-status')) {
                document.getElementById('busca-vistorias-status').value = FILTRO_INICIAL;
            }
            abrirSecao(ABA_INICIAL);
            carregarPainel();
            carregarSolicitacoes();
            carregarUltimasVistorias();
        });
    </script>
</body>

</html>
