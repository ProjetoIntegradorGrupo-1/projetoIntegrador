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
$stFoto = $pdo->prepare("SELECT foto FROM Usuarios WHERE id_usuario = :id");
$stFoto->execute([':id' => $eu['id']]);
$minhaFoto = $stFoto->fetchColumn() ?: null;

$isAdmin      = ($perfil_usuario === 'admin');
$isSupervisor = ($perfil_usuario === 'supervisor');
$isGestor     = $isAdmin || $isSupervisor;
$isMotorista  = ($perfil_usuario === 'motorista');
$isCliente    = ($perfil_usuario === 'cliente');
// Vistoria é operacional (supervisor e motorista); o admin cuida da gestão do sistema
$podeVistoriar = $isSupervisor || $isMotorista;

// Solicitações que dependem da assinatura do usuário logado
$stPend = $pdo->prepare("SELECT COUNT(*) FROM Checklist_Execucoes WHERE recusado_em IS NULL AND
    ((id_motorista = :a AND assinado_motorista_em IS NULL) OR (id_vistoriador = :b AND assinado_vistoriador_em IS NULL))");
$stPend->execute([':a' => $eu['id'], ':b' => $eu['id']]);
$minhasPendencias = (int)$stPend->fetchColumn();
// Pendência do admin: modelos de checklist aguardando aprovação
if ($isAdmin) {
    $minhasPendencias = (int)$pdo->query("SELECT COUNT(*) FROM Checklists WHERE status_aprovacao = 'pendente'")->fetchColumn();
}

// Pedidos de locação aguardando admin/supervisor
$locacoesPendentes = $isGestor ? (int)$pdo->query("SELECT COUNT(*) FROM Locacoes WHERE status = 'pendente'")->fetchColumn() : 0;
// Clientes que se cadastraram sozinhos e aguardam aprovação
$clientesPendentes = $isGestor ? (int)$pdo->query("SELECT COUNT(*) FROM Usuarios WHERE perfil = 'cliente' AND status = 'pendente'")->fetchColumn() : 0;

// Menu de acordo com o perfil (o mesmo é montado pelo axion.js nas outras telas)
if ($isAdmin) {
    $menu = [
        'inicio'     => ['bi-speedometer2', 'Painel'],
        'aprovacoes' => ['bi-patch-check', 'Aprovações'],
        'locacoes'   => ['bi-key', 'Locações'],
        'usuarios'   => ['bi-people', 'Usuários'],
        'checklists' => ['bi-ui-checks-grid', 'Modelos de checklist'],
        'frota'      => ['bi-truck-front', 'Frota'],
        'vistorias'  => ['bi-clipboard2-check', 'Vistorias'],
        'admin'      => ['bi-gear', 'Administração'],
    ];
} elseif ($isSupervisor) {
    $menu = [
        'inicio'     => ['bi-speedometer2', 'Painel'],
        'vistorias'  => ['bi-clipboard2-check', 'Vistorias da equipe'],
        'locacoes'   => ['bi-key', 'Locações'],
        'usuarios'   => ['bi-people', 'Minha equipe'],
        'frota'      => ['bi-truck-front', 'Frota'],
        'checklists' => ['bi-ui-checks-grid', 'Checklists'],
    ];
} elseif ($isMotorista) {
    $menu = [
        'inicio'    => ['bi-speedometer2', 'Painel'],
        'vistorias' => ['bi-clipboard2-check', 'Minhas vistorias'],
        'frota'     => ['bi-truck-front', 'Frota'],
    ];
} else {
    // Cliente: só aluga veículos e acompanha as próprias locações
    $menu = [
        'inicio'   => ['bi-house-door', 'Início'],
        'locar'    => ['bi-car-front', 'Alugar um veículo'],
        'locacoes' => ['bi-calendar-check', 'Minhas locações'],
    ];
}
$secaoPendencia = $isAdmin ? 'aprovacoes' : ($isCliente ? 'locacoes' : 'vistorias');

// Compatibilidade com os links antigos (?aba=veiculos / ?aba=checklists)
$aba = $_GET['aba'] ?? 'inicio';
$aba = ['veiculos' => 'frota'][$aba] ?? $aba;
if (!isset($menu[$aba])) $aba = 'inicio';

$subtitulos = [
    'admin'      => 'Gestão do sistema: aprove os checklists dos supervisores, administre os usuários e compare as equipes.',
    'supervisor' => 'Acompanhe a sua equipe e aprove as vistorias que chegam para você.',
    'motorista'  => 'Faça suas vistorias e acompanhe o que ainda falta assinar.',
    'cliente'    => 'Escolha um veículo disponível, peça a locação e acompanhe o prazo de devolução.',
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
    'checklist_criado'      => 'Modelo de checklist criado e já disponível para as vistorias.',
    'checklist_enviado_aprovacao' => 'Modelo enviado para o administrador aprovar. Ele aparece para as vistorias assim que for aprovado.',
    'checklist_editado'     => 'Alterações do modelo de checklist salvas.',
    'checklist_excluido'    => 'Modelo de checklist excluído.',
    'checklist_arquivado'   => 'Modelo arquivado: ele já tem vistorias registradas, por isso não foi apagado. Para usar de novo, edite e salve o modelo.',
    'usuario_desativado'    => 'Conta desativada: este usuário já tem histórico (vistorias ou locações), por isso não foi apagado. Para reativar, edite o usuário.',
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
    <link rel="icon" type="image/png" href="frontend/img/favicon.png">
</head>

<body class="<?php echo $isAdmin ? 'tema-admin' : ''; ?>">
    <nav class="axion-navbar text-white">
        <div class="container-fluid px-3 px-lg-4 d-flex flex-nowrap justify-content-between align-items-center gap-2">
            <div class="d-flex align-items-center gap-2 min-w-0">
            <button type="button" class="btn btn-sm btn-outline-light d-lg-none btn-menu-mobile" data-abrir-menu aria-label="Abrir menu"><i class="bi bi-list fs-5"></i></button>
            <a href="oqfazer.php" class="axion-brand">
                <img src="frontend/img/logo.png" class="axion-logo" alt="Logo Axion">
                <span>AXION</span>
                <span class="axion-brand-badge"><?php echo $isAdmin ? 'Administração' : ($isCliente ? 'Locação de veículos' : 'Frota & Checklists'); ?></span>
            </a>
            </div>
            <div class="d-flex align-items-center gap-2 gap-md-3">
                <?php if ($minhasPendencias > 0): ?>
                <a href="<?php echo $isAdmin ? 'oqfazer.php?aba=aprovacoes' : ($isCliente ? 'oqfazer.php?aba=locacoes' : 'oqfazer.php?aba=vistorias&filtro=minhas_pendencias'); ?>" class="btn btn-sm btn-warning fw-semibold">
                    <i class="bi bi-bell-fill me-1"></i><?php echo $minhasPendencias; ?><span class="d-none d-sm-inline"><?php echo ($isMotorista || $isCliente) ? ' para assinar' : ' para aprovar'; ?></span>
                </a>
                <?php endif; ?>
                <?php if ($locacoesPendentes > 0): ?>
                <a href="oqfazer.php?aba=locacoes&filtro=pendente" class="btn btn-sm btn-info fw-semibold" title="Pedidos de locação aguardando aprovação">
                    <i class="bi bi-key-fill me-1"></i><?php echo $locacoesPendentes; ?><span class="d-none d-sm-inline"> <?php echo $locacoesPendentes > 1 ? 'locações' : 'locação'; ?></span>
                </a>
                <?php endif; ?>
                <a href="meuPerfil.html" class="d-flex align-items-center gap-2 small border-start ps-3 border-light border-opacity-25 text-white text-decoration-none" title="Meu perfil">
                    <?php if ($minhaFoto): ?>
                    <img src="backend/api.php?acao=foto_usuario&id=<?php echo (int)$eu['id']; ?>&v=<?php echo urlencode(pathinfo($minhaFoto, PATHINFO_FILENAME)); ?>" alt="" class="axion-avatar" style="width:34px;height:34px">
                    <?php else: ?>
                    <span class="axion-avatar axion-avatar-iniciais" style="width:34px;height:34px;font-size:14px"><?php
                        $partesNome = preg_split('/\s+/', trim($nome_usuario));
                        echo htmlspecialchars(mb_strtoupper(mb_substr($partesNome[0], 0, 1) . (count($partesNome) > 1 ? mb_substr(end($partesNome), 0, 1) : '')));
                    ?></span>
                    <?php endif; ?>
                    <div class="lh-sm d-none d-md-block">
                        <div class="fw-semibold"><?php echo htmlspecialchars($nome_usuario); ?></div>
                        <div class="text-white-50"><?php echo ucfirst(htmlspecialchars($perfil_usuario)); ?> · Meu perfil</div>
                    </div>
                </a>
                <a href="logout.php" class="btn btn-sm btn-outline-light" title="Sair"><i class="bi bi-box-arrow-right"></i><span class="d-none d-sm-inline ms-1">Sair</span></a>
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
                    <?php if ($chave === $secaoPendencia && $minhasPendencias > 0): ?>
                    <span class="badge rounded-pill bg-warning text-dark"><?php echo $minhasPendencias; ?></span>
                    <?php elseif ($chave === 'locacoes' && $locacoesPendentes > 0): ?>
                    <span class="badge rounded-pill bg-info text-dark"><?php echo $locacoesPendentes; ?></span>
                    <?php elseif ($chave === 'usuarios' && $clientesPendentes > 0): ?>
                    <span class="badge rounded-pill bg-success" id="badge-clientes-pendentes"><?php echo $clientesPendentes; ?></span>
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
                        <a href="criarChecklist.html" class="btn btn-outline-primary btn-sm" title="<?php echo $isSupervisor ? 'Vai para aprovação do administrador' : ''; ?>"><i class="bi bi-ui-checks me-1"></i>Criar checklist</a>
                        <?php endif; ?>
                        <?php if ($podeVistoriar): ?>
                        <a href="cadcheck.html" class="btn btn-warning btn-sm fw-semibold"><i class="bi bi-plus-circle me-1"></i>Nova vistoria</a>
                        <?php endif; ?>
                        <?php if ($isCliente): ?>
                        <a href="#locar" data-secao="locar" class="btn btn-primary btn-sm fw-semibold"><i class="bi bi-car-front me-1"></i>Alugar um veículo</a>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($minhasPendencias > 0 && !$isCliente): ?>
                <div class="card border-warning shadow-sm mb-3">
                    <div class="card-header bg-warning-subtle fw-semibold">
                        <i class="bi bi-bell-fill text-warning-emphasis me-1"></i>
                        <?php echo $isAdmin ? 'Modelos de checklist aguardando a sua aprovação' : ($isSupervisor ? 'Vistorias aguardando a sua aprovação' : 'Vistorias aguardando a sua assinatura'); ?> (<?php echo $minhasPendencias; ?>)
                    </div>
                    <div class="list-group list-group-flush" id="lista-solicitacoes">
                        <div class="list-group-item text-muted small">Carregando...</div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($locacoesPendentes > 0): ?>
                <div class="alert alert-info d-flex flex-wrap align-items-center justify-content-between gap-2 shadow-sm">
                    <div><i class="bi bi-key-fill me-1"></i><strong><?php echo $locacoesPendentes; ?></strong> <?php echo $locacoesPendentes > 1 ? 'pedidos de locação aguardando' : 'pedido de locação aguardando'; ?> a sua aprovação.</div>
                    <a href="#locacoes" data-secao="locacoes" class="btn btn-sm btn-info fw-semibold">Ver pedidos</a>
                </div>
                <?php endif; ?>

                <?php if ($clientesPendentes > 0): ?>
                <div class="alert alert-success d-flex flex-wrap align-items-center justify-content-between gap-2 shadow-sm" id="alerta-clientes-pendentes">
                    <div><i class="bi bi-person-plus-fill me-1"></i><strong><?php echo $clientesPendentes; ?></strong> <?php echo $clientesPendentes > 1 ? 'novos clientes se cadastraram e aguardam' : 'novo cliente se cadastrou e aguarda'; ?> a sua aprovação.</div>
                    <a href="#usuarios" data-secao="usuarios" class="btn btn-sm btn-success fw-semibold">Ver cadastros</a>
                </div>
                <?php endif; ?>

                <!-- Indicadores (preenchidos pelo JS conforme o perfil) -->
                <div class="row g-3 mb-3" id="kpis"></div>

                <!-- Gráficos e quadros do perfil -->
                <div class="row g-3" id="graficos">
                    <?php if ($isAdmin): ?>
                    <div class="col-xl-7"><div class="cartao"><h3>Comparativo entre equipes</h3><div class="cartao-sub">Vistorias aprovadas por cada supervisor nos últimos 30 dias</div><div class="grafico"><canvas id="g-supervisores"></canvas></div></div></div>
                    <div class="col-xl-5"><div class="cartao">
                        <h3>Supervisores</h3><div class="cartao-sub">Tamanho da equipe e o que está parado com cada um</div>
                        <div class="table-responsive"><table class="table table-sm align-middle mb-0">
                            <thead><tr><th>Supervisor</th><th class="text-center">Motoristas</th><th class="text-center">Vistorias p/ aprovar</th><th class="text-center">Checklists</th></tr></thead>
                            <tbody id="tbody-supervisores"><tr><td colspan="4" class="text-muted small">Carregando...</td></tr></tbody>
                        </table></div>
                    </div></div>
                    <div class="col-xl-4"><div class="cartao"><h3>Usuários ativos por perfil</h3><div class="cartao-sub" id="sub-usuarios">Contas que podem entrar no sistema</div><div class="grafico"><canvas id="g-usuarios"></canvas></div></div></div>
                    <div class="col-xl-4"><div class="cartao"><h3>Modelos de checklist</h3><div class="cartao-sub">Situação da aprovação</div><div class="grafico"><canvas id="g-checklists"></canvas></div></div></div>
                    <div class="col-xl-4"><div class="cartao"><h3>Frota por situação</h3><div class="cartao-sub">Veículos cadastrados</div><div class="grafico"><canvas id="g-frota"></canvas></div></div></div>
                    <div class="col-xl-8"><div class="cartao"><h3>Vistorias no sistema (14 dias)</h3><div class="cartao-sub">Todas as equipes, por dia</div><div class="grafico"><canvas id="g-dias"></canvas></div></div></div>
                    <div class="col-xl-4"><div class="cartao"><h3>Veículos com mais avarias</h3><div class="cartao-sub">Não conformes nos últimos 90 dias</div><div class="grafico"><canvas id="g-avarias"></canvas></div></div></div>
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
                    <div class="col-xl-5"><div class="cartao">
                        <h3>Meus checklists</h3><div class="cartao-sub">Modelos que você criou e a situação da aprovação pelo administrador</div>
                        <div id="meus-checklists" class="d-flex flex-column gap-2"><span class="text-muted small">Carregando...</span></div>
                        <a href="oqfazer.php?aba=checklists&filtro=meus" class="btn btn-sm btn-outline-primary mt-3">Ver meus checklists</a>
                    </div></div>
                    <div class="col-xl-7"><div class="cartao"><h3>Veículos com mais avarias</h3><div class="cartao-sub">Vistorias não conformes da equipe (90 dias)</div><div class="grafico"><canvas id="g-avarias"></canvas></div></div></div>
                    <?php elseif ($isMotorista): ?>
                    <div class="col-xl-7"><div class="cartao"><h3>Suas vistorias nos últimos 14 dias</h3><div class="cartao-sub">Conformes e não conformes por dia</div><div class="grafico"><canvas id="g-dias"></canvas></div></div></div>
                    <div class="col-xl-5"><div class="cartao">
                        <h3>Últimas vistorias</h3><div class="cartao-sub">Clique para ver os detalhes</div>
                        <div class="list-group list-group-flush" id="ultimas-vistorias"><div class="text-muted small">Carregando...</div></div>
                    </div></div>
                    <?php else: ?>
                    <div class="col-xl-7"><div class="cartao h-100">
                        <h3>Sua locação</h3><div class="cartao-sub">Veículo que está com você e o prazo para devolver</div>
                        <div id="cliente-locacao-atual"><span class="text-muted small">Carregando...</span></div>
                    </div></div>
                    <div class="col-xl-5"><div class="cartao h-100">
                        <h3>Disponíveis para alugar</h3><div class="cartao-sub">Alguns dos veículos livres agora</div>
                        <div id="cliente-destaques" class="d-flex flex-column gap-2"><span class="text-muted small">Carregando...</span></div>
                        <a href="#locar" data-secao="locar" class="btn btn-primary mt-3 w-100"><i class="bi bi-car-front me-1"></i>Ver todos e alugar</a>
                    </div></div>
                    <?php endif; ?>
                </div>
            </section>

            <?php if (!$isCliente): ?>
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
                                <?php if ($podeVistoriar): ?><option value="minhas_pendencias">Aguardando a minha assinatura</option><?php endif; ?>
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
                        <div class="col-md-5"><input type="search" id="busca-veiculos-q" class="form-control" placeholder="Buscar por placa, marca, modelo, cor, Renavam ou chassi..." oninput="buscarComAtraso('veiculos')"></div>
                        <div class="col-md-3<?php echo $perfil_usuario === 'motorista' ? ' d-none' : ''; ?>">
                            <select id="busca-veiculos-finalidade" class="form-select" onchange="carregarBusca('veiculos')">
                                <option value="">Frota e locação</option>
                                <option value="frota">Só frota da empresa</option>
                                <option value="locacao">Só locação</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <select id="busca-veiculos-status" class="form-select" onchange="carregarBusca('veiculos')">
                                <option value="">Todas as situações</option>
                                <option value="ativo">Ativo</option>
                                <option value="em_rota">Em rota</option>
                                <option value="manutencao">Em manutenção</option>
                                <option value="locado">Locado</option>
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
            <?php endif; ?>

            <?php if ($isCliente): ?>
            <!-- ===================== ALUGAR UM VEÍCULO (CLIENTE) ===================== -->
            <section class="secao" id="secao-locar">
                <div class="secao-topo">
                    <div><h1>Alugar um veículo</h1><p>Veículos livres agora. Escolha um, informe as datas e a empresa aprova o seu pedido.</p></div>
                </div>
                <div id="aviso-cnh"></div>
                <div class="row g-3" id="grade-veiculos"><div class="col-12 text-muted">Carregando...</div></div>
            </section>

            <!-- ===================== MINHAS LOCAÇÕES (CLIENTE) ===================== -->
            <section class="secao" id="secao-locacoes">
                <div class="secao-topo">
                    <div><h1>Minhas locações</h1><p>Seus pedidos, o que está com você e quando precisa devolver.</p></div>
                    <a href="#locar" data-secao="locar" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Alugar outro veículo</a>
                </div>
                <div class="d-flex flex-column gap-3" id="lista-minhas-locacoes"><span class="text-muted">Carregando...</span></div>
            </section>

            <!-- Pedido de locação -->
            <div class="modal fade" id="modal-locacao" tabindex="-1" aria-labelledby="titulo-modal-locacao" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <form class="modal-content" id="form-locacao">
                        <div class="modal-header">
                            <h5 class="modal-title" id="titulo-modal-locacao"><i class="bi bi-key me-1"></i>Pedir locação</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                        </div>
                        <div class="modal-body">
                            <div id="locacao-veiculo" class="d-flex align-items-center gap-3 mb-3"></div>
                            <input type="hidden" name="id_veiculo" id="locacao-id-veiculo">
                            <div class="row g-2">
                                <div class="col-6">
                                    <label for="locacao-retirada" class="form-label fw-semibold">Retirada</label>
                                    <input type="date" class="form-control" id="locacao-retirada" name="data_retirada" required>
                                </div>
                                <div class="col-6">
                                    <label for="locacao-devolucao" class="form-label fw-semibold">Devolução</label>
                                    <input type="date" class="form-control" id="locacao-devolucao" name="data_devolucao" required>
                                </div>
                            </div>
                            <div class="form-text mb-2" id="locacao-dias"></div>
                            <label for="locacao-obs" class="form-label fw-semibold mt-2">Observação <span class="text-muted fw-normal">(opcional)</span></label>
                            <textarea class="form-control" id="locacao-obs" name="observacao" rows="2" maxlength="255" placeholder="Ex.: preciso retirar à tarde"></textarea>
                            <div class="alert alert-danger small mt-3 mb-0 d-none" id="locacao-erro"></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary" id="locacao-enviar"><i class="bi bi-send me-1"></i>Enviar pedido</button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($isGestor): ?>
            <!-- ===================== LOCAÇÕES (ADMIN E SUPERVISOR) ===================== -->
            <section class="secao" id="secao-locacoes">
                <div class="secao-topo">
                    <div><h1>Locações</h1><p>Pedidos dos clientes, veículos que estão com eles e as devoluções.</p></div>
                </div>
                <div class="row g-3 mb-3" id="kpis-locacoes"></div>
                <div class="cartao mb-3">
                    <div class="row g-2">
                        <div class="col-md-5">
                            <select id="busca-locacoes-status" class="form-select" onchange="carregarLocacoesGestor()">
                                <option value="">Todas</option>
                                <option value="pendente">Aguardando aprovação</option>
                                <option value="ativa">Em andamento</option>
                                <option value="atrasada">Atrasadas</option>
                                <option value="encerradas">Encerradas (devolvidas, recusadas e canceladas)</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="axion-table-wrapper">
                    <div class="table-responsive">
                        <table class="table table-hover axion-table mb-0">
                            <thead><tr><th>Cliente</th><th>Veículo</th><th>Período e situação</th><th class="text-end">Ações</th></tr></thead>
                            <tbody id="tbody-locacoes"><tr><td colspan="4" class="text-center py-4 text-muted">Carregando...</td></tr></tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- Ficha do cliente -->
            <div class="modal fade" id="modal-ficha-cliente" tabindex="-1" aria-labelledby="titulo-ficha-cliente" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="titulo-ficha-cliente"><i class="bi bi-person-vcard me-1"></i>Dados do cliente</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                        </div>
                        <div class="modal-body" id="corpo-ficha-cliente"></div>
                    </div>
                </div>
            </div>

            <!-- Recusar pedido / registrar devolução -->
            <div class="modal fade" id="modal-acao-locacao" tabindex="-1" aria-labelledby="titulo-acao-locacao" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <form class="modal-content" id="form-acao-locacao">
                        <div class="modal-header">
                            <h5 class="modal-title" id="titulo-acao-locacao"></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                        </div>
                        <div class="modal-body">
                            <div id="acao-locacao-resumo" class="small text-muted mb-3"></div>
                            <div id="acao-locacao-campo"></div>
                            <div class="alert alert-danger small mt-3 mb-0 d-none" id="acao-locacao-erro"></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Voltar</button>
                            <button type="submit" class="btn" id="acao-locacao-enviar"></button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($isGestor): ?>
            <!-- ===================== USUÁRIOS ===================== -->
            <section class="secao" id="secao-usuarios">
                <div class="secao-topo">
                    <div>
                        <h1><?php echo $isSupervisor ? 'Minha equipe' : 'Usuários'; ?></h1>
                        <p><?php echo $isSupervisor ? 'Seus motoristas e os clientes. Contas de supervisor e de administrador são gerenciadas pelo administrador.' : 'Todas as contas do sistema, de todos os perfis.'; ?></p>
                    </div>
                    <a href="caduser.html" class="btn btn-success"><i class="bi bi-person-plus me-1"></i>Cadastrar usuário</a>
                </div>
                <?php if ($isGestor): ?>
                <div class="cartao mb-3 border-success d-none" id="card-clientes-pendentes">
                    <h3><i class="bi bi-person-plus text-success me-1"></i>Clientes aguardando aprovação</h3>
                    <div class="cartao-sub">Cadastros feitos pelos próprios clientes. Confira os dados, escolha o supervisor responsável (testemunha das vistorias) e aprove.</div>
                    <div id="lista-clientes-pendentes" class="d-flex flex-column gap-2"></div>
                </div>
                <?php endif; ?>
                <div class="cartao mb-3">
                    <div class="row g-2">
                        <div class="col-md-7"><input type="search" id="busca-usuarios-q" class="form-control" placeholder="Buscar por nome, CPF (com ou sem pontos), e-mail ou cidade..." oninput="buscarComAtraso('usuarios')"></div>
                        <div class="col-md-5">
                            <select id="busca-usuarios-perfil" class="form-select" onchange="carregarBusca('usuarios')">
                                <option value="">Todos os perfis</option>
                                <?php if ($isSupervisor): ?><option value="minha_equipe" selected>Minha equipe</option><?php endif; ?>
                                <option value="motorista">Motoristas</option>
                                <option value="cliente">Clientes</option>
                                <?php if ($isAdmin): ?>
                                <option value="supervisor">Supervisores</option>
                                <option value="admin">Administradores</option>
                                <?php endif; ?>
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

            <?php if ($isAdmin): ?>
            <!-- ===================== APROVAÇÕES (ADMIN) ===================== -->
            <section class="secao" id="secao-aprovacoes">
                <div class="secao-topo">
                    <div><h1>Aprovações</h1><p>Modelos de checklist criados ou alterados pelos supervisores. Revise as perguntas e aprove ou rejeite.</p></div>
                </div>
                <div class="axion-table-wrapper">
                    <div class="table-responsive">
                        <table class="table table-hover axion-table mb-0">
                            <thead><tr><th>Modelo</th><th>Categoria</th><th>Perguntas</th><th>Enviado por</th><th class="text-end">Ação</th></tr></thead>
                            <tbody id="tbody-aprovacoes"><tr><td colspan="5" class="text-center py-4 text-muted">Carregando...</td></tr></tbody>
                        </table>
                    </div>
                </div>
            </section>
            <?php endif; ?>

            <!-- ===================== MODELOS DE CHECKLIST ===================== -->
            <section class="secao" id="secao-checklists">
                <div class="secao-topo">
                    <div>
                        <h1><?php echo $isSupervisor ? 'Checklists' : 'Modelos de checklist'; ?></h1>
                        <p><?php echo $isSupervisor ? 'Modelos aprovados para usar nas vistorias e os que você criou. Modelo novo ou alterado vai para o administrador aprovar.' : 'Todos os modelos. Os criados por supervisores só ficam disponíveis depois da sua aprovação.'; ?></p>
                    </div>
                    <a href="criarChecklist.html" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i><?php echo $isSupervisor ? 'Criar e enviar para aprovação' : 'Criar modelo'; ?></a>
                </div>
                <div class="cartao mb-3">
                    <div class="row g-2">
                        <div class="col-md-8"><input type="search" id="busca-checklists-q" class="form-control" placeholder="Buscar por título ou descrição..." oninput="buscarComAtraso('checklists')"></div>
                        <div class="col-md-4">
                            <select id="busca-checklists-cat" class="form-select" onchange="carregarBusca('checklists')">
                                <option value="">Todos</option>
                                <?php if ($isSupervisor): ?><option value="meus">Criados por mim</option><?php endif; ?>
                                <option value="pendente">Aguardando aprovação</option>
                                <option value="rejeitado">Rejeitados</option>
                                <option value="aprovado">Aprovados</option>
                                <option disabled>── Categoria ──</option>
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
                            <thead><tr><th>Modelo</th><th>Aprovação</th><th>Perguntas</th><th>Vistorias</th><th>Criado por</th><th class="text-end">Ações</th></tr></thead>
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
        const IS_SUPERVISOR = <?php echo $isSupervisor ? 'true' : 'false'; ?>;
        const PODE_VISTORIAR = <?php echo $podeVistoriar ? 'true' : 'false'; ?>;
        const IS_CLIENTE = <?php echo $isCliente ? 'true' : 'false'; ?>;
        const CSRF = <?php echo json_encode($csrf); ?>;
        const MEU_ID = <?php echo (int)$eu['id']; ?>;
        const ABA_INICIAL = <?php echo json_encode($aba); ?>;
        const FILTRO_INICIAL = new URLSearchParams(location.search).get('filtro') || '';

        // Escapa textos do banco antes de colocar no HTML
        const esc = (v) => String(v ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        // Foto de perfil redonda (ou iniciais do nome)
        function avatar(url, nome, t) {
            const est = `width:${t}px;height:${t}px;font-size:${Math.round(t * 0.4)}px`;
            if (url) return `<img src="${esc(url)}" alt="" class="axion-avatar borda-escura" style="${est}">`;
            const p = String(nome || '?').trim().split(/\s+/);
            return `<span class="axion-avatar axion-avatar-iniciais borda-escura" style="${est}">${esc(((p[0] || '?')[0] + (p.length > 1 ? p[p.length - 1][0] : '')).toUpperCase())}</span>`;
        }
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
                if (nome === 'aprovacoes') carregarAprovacoes();
                if (nome === 'locar') carregarVeiculosDisponiveis();
                if (nome === 'locacoes') IS_CLIENTE ? carregarMinhasLocacoes() : carregarLocacoesGestor();
            }
            window.scrollTo({ top: 0 });
        }
        document.querySelectorAll('[data-secao]').forEach(a => a.addEventListener('click', (ev) => { ev.preventDefault(); abrirSecao(a.dataset.secao); document.body.classList.remove('menu-aberto'); }));
        // Celular: cada linha das listas vira um cartão; cada célula recebe o nome da coluna como rótulo
        function rotularTabelas() {
            document.querySelectorAll('table.axion-table').forEach(t => {
                const nomes = [...t.querySelectorAll('thead th')].map(th => th.textContent.trim());
                t.querySelectorAll('tbody tr').forEach(tr => [...tr.children].forEach((td, i) => {
                    if (!td.hasAttribute('colspan') && nomes[i] !== undefined) td.dataset.label = nomes[i];
                }));
            });
        }
        new MutationObserver(rotularTabelas).observe(document.body, { childList: true, subtree: true });
        rotularTabelas();

        // Menu no celular: abre como gaveta pelo botão ☰ e fecha ao tocar fora
        document.addEventListener('click', (ev) => {
            if (ev.target.closest('[data-abrir-menu]')) { document.body.classList.toggle('menu-aberto'); return; }
            if (document.body.classList.contains('menu-aberto') && !ev.target.closest('.painel-menu')) document.body.classList.remove('menu-aberto');
        });
        document.addEventListener('keydown', (ev) => { if (ev.key === 'Escape') document.body.classList.remove('menu-aberto'); });

        // ---------- Etiquetas ----------
        function badgeStatusVeiculo(st) {
            if (st === 'ativo') return '<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check-circle me-1"></i>Ativo</span>';
            if (st === 'em_rota') return '<span class="badge bg-primary-subtle text-primary border border-primary-subtle"><i class="bi bi-signpost-2 me-1"></i>Em rota</span>';
            if (st === 'manutencao') return '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle"><i class="bi bi-tools me-1"></i>Manutenção</span>';
            if (st === 'locado') return '<span class="badge bg-info-subtle text-info-emphasis border border-info-subtle"><i class="bi bi-key me-1"></i>Locado</span>';
            return `<span class="badge bg-secondary">${esc(st)}</span>`;
        }
        function badgePerfil(p) {
            return { admin: '<span class="badge bg-dark">Admin</span>', supervisor: '<span class="badge bg-warning text-dark">Supervisor</span>', motorista: '<span class="badge bg-primary">Motorista</span>' }[p] || '<span class="badge bg-info text-dark">Cliente</span>';
        }
        function badgeConformidade(st) {
            return st === 'nao_conforme' ? '<span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Não conforme</span>' : '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Conforme</span>';
        }
        const CATEGORIAS = { eletrico: 'Elétrico', mecanico: 'Mecânico', estetico: 'Estético', seguranca: 'Segurança', pre_viagem: 'Pré-viagem', locacao: 'Locação', outros: 'Outros' };
        const nomeCategoria = (c) => CATEGORIAS[c] || c || '-';
        const nomesCategoriasChecklist = (c) => {
            const lista = (c.categorias_perguntas || c.categoria || '').split(',').map(s => s.trim()).filter(Boolean);
            return lista.length ? lista.map(k => CATEGORIAS[k] || k).join(', ') : '-';
        };
        function badgeAprovacao(st) {
            return {
                pendente: '<span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i>Aguardando</span>',
                aprovado: '<span class="badge bg-success-subtle text-success"><i class="bi bi-check-circle me-1"></i>Aprovado</span>',
                rejeitado: '<span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Rejeitado</span>'
            }[st] || '';
        }

        async function carregarAprovacoes() {
            const tbody = document.getElementById('tbody-aprovacoes');
            if (!tbody) return;
            const json = await getJson('backend/api.php?acao=buscar&tipo=checklists&filtro=pendente&limite=250');
            const dados = json.dados || [];
            tbody.innerHTML = dados.length ? dados.map(c => `
                <tr>
                    <td><div class="fw-semibold">${esc(c.titulo)}</div><div class="small text-muted">${esc(c.descricao || '')}</div></td>
                    <td>${esc(nomesCategoriasChecklist(c))}</td>
                    <td>${c.total_perguntas}</td>
                    <td class="small">${esc(c.criador_nome)}</td>
                    <td class="text-end"><a href="verChecklist.html?id=${c.id_checklist}" class="btn btn-sm btn-warning fw-semibold"><i class="bi bi-patch-check me-1"></i>Revisar</a></td>
                </tr>`).join('') : '<tr><td colspan="5" class="text-center py-4 text-muted"><i class="bi bi-check2-all me-1"></i>Nenhum modelo aguardando aprovação.</td></tr>';
        }

        function badgeSituacao(sit) {
            return {
                concluida: '<span class="badge bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle">Concluída</span>',
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
                const extra = tipo === 'veiculos' ? `&finalidade=${encodeURIComponent(val('busca-veiculos-finalidade'))}` : '';
                const json = await getJson(`backend/api.php?acao=buscar&tipo=${tipo}&q=${encodeURIComponent(cfg[2])}&filtro=${encodeURIComponent(cfg[3])}&limite=250${extra}`);
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
                    <td class="text-muted small font-monospace">#${v.id_execucao}</td>
                    <td class="small">${dataBR(v.data_vistoria)} <span class="text-muted font-monospace">${esc((v.hora_vistoria || '').slice(0, 5))}</span></td>
                    <td><span class="badge bg-dark font-monospace">${esc(v.placa_veiculo)}</span></td>
                    <td class="small fw-semibold">${esc(v.checklist_titulo)}${v.id_locacao ? `<div><span class="badge bg-info-subtle text-info-emphasis"><i class="bi bi-key me-1"></i>Locação · ${v.tipo_locacao === 'retirada' ? 'retirada' : 'devolução'}</span></div>` : ''}</td>
                    <td class="small">${esc(v.nome_motorista)}${v.id_locacao ? ' <span class="text-muted">(cliente)</span>' : ''}</td>
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
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            ${v.foto_url ? `<img src="${esc(v.foto_url)}" alt="" style="width:64px;height:48px;object-fit:cover;border-radius:6px">` : '<span class="d-inline-flex align-items-center justify-content-center bg-light border text-muted" style="width:64px;height:48px;border-radius:6px"><i class="bi bi-truck"></i></span>'}
                            <div><div class="fw-semibold">${esc(v.marca_modelo)}</div>${v.finalidade === 'locacao'
                                ? '<span class="badge bg-info-subtle text-info-emphasis border border-info-subtle"><i class="bi bi-key me-1"></i>Locação</span>'
                                : '<span class="badge bg-light text-secondary border"><i class="bi bi-building me-1"></i>Frota da empresa</span>'}</div>
                        </div>
                    </td>
                    <td class="font-monospace">${esc(v.ano)}</td>
                    <td>${esc(v.cor || '-')}</td>
                    <td class="font-monospace">${Number(v.km_rodado || 0).toLocaleString('pt-BR')} km</td>
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
                        <div class="d-flex align-items-center gap-2">
                            ${avatar(u.foto_url, u.nome, 36)}
                            <div>
                                <div class="fw-semibold">${esc(u.nome)} ${u.status === 'inativo' ? '<span class="badge bg-secondary">Desativado</span>' : ''}${u.status === 'pendente' ? '<span class="badge bg-success">Aguardando aprovação</span>' : ''}</div>
                                ${['motorista', 'cliente'].includes(u.perfil) ? `<div class="small text-muted">Supervisor: ${esc(u.supervisor_nome || 'não definido')}</div>` : ''}
                            </div>
                        </div>
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
            checklists: (c) => {
                const podeGerir = IS_ADMIN || Number(c.id_criador) === MEU_ID;
                const infoVeic = c.veiculo_placa ? `<span class="badge bg-dark font-monospace me-1">${esc(c.veiculo_placa)}</span>` : '<span class="badge bg-light text-secondary border me-1">Frota Geral</span>';
                return `
                <tr>
                    <td>
                        <div class="fw-semibold">${esc(c.titulo)} ${Number(c.ativo) === 1 ? '' : '<span class="badge bg-secondary">Arquivado</span>'}</div>
                        <div class="small text-muted mt-1">${infoVeic}Categorias das perguntas: ${esc(nomesCategoriasChecklist(c))}${c.descricao ? ' · ' + esc(c.descricao) : ''}</div>
                    </td>
                    <td>${badgeAprovacao(c.status_aprovacao)}${c.status_aprovacao === 'rejeitado' && c.motivo_rejeicao ? `<div class="small text-danger mt-1">${esc(c.motivo_rejeicao)}</div>` : ''}</td>
                    <td>${c.total_perguntas}</td>
                    <td>${c.total_execucoes}</td>
                    <td class="small">${esc(c.criador_nome)}</td>
                    <td class="text-end text-nowrap">
                        ${IS_ADMIN && c.status_aprovacao === 'pendente' ? `<a href="verChecklist.html?id=${c.id_checklist}" class="btn btn-sm btn-warning fw-semibold"><i class="bi bi-patch-check"></i> Revisar</a>` : ''}
                        ${PODE_VISTORIAR && c.status_aprovacao === 'aprovado' && Number(c.ativo) === 1 ? `<a href="cadcheck.html?id_checklist=${c.id_checklist}" class="btn btn-sm btn-warning" title="Usar em uma vistoria"><i class="bi bi-clipboard2-plus"></i></a>` : ''}
                        <a href="verChecklist.html?id=${c.id_checklist}" class="btn btn-sm btn-outline-secondary" title="Ver perguntas"><i class="bi bi-eye"></i></a>
                        ${podeGerir ? `<a href="editarChecklist.html?id=${c.id_checklist}" class="btn btn-sm btn-outline-primary" title="Editar"><i class="bi bi-pencil"></i></a>
                        <a href="excluirChecklist.html?id=${c.id_checklist}" class="btn btn-sm btn-outline-danger" title="Excluir"><i class="bi bi-trash"></i></a>` : ''}
                    </td>
                </tr>`;
            }
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
            const frotaTotal = d.frota.ativo + d.frota.em_rota + d.frota.manutencao + d.frota.locado;
            let cards = [];
            if (PERFIL === 'admin') {
                const u = d.usuarios || {};
                const totalUsu = Object.values(u).reduce((a, b) => a + Number(b), 0);
                const cs = d.checklists_status || {};
                cards = [
                    kpi('bi-patch-check', 'Checklists para aprovar', Number(cs.pendente || 0), Number(cs.pendente || 0) ? 'Enviados pelos supervisores' : 'Nada pendente', '#b07c00'),
                    kpi('bi-people', 'Usuários ativos', totalUsu, `${u.supervisor || 0} supervisores · ${u.motorista || 0} motoristas · ${u.cliente || 0} clientes`),
                    kpi('bi-truck-front', 'Veículos na frota', frotaTotal, `${d.frota.locado} locados · ${d.frota.manutencao} em manutenção`),
                    kpi('bi-shield-check', 'Conformidade geral', taxa, `${v.total} vistorias · ${v.nao_conformes} não conformes`, COR.conforme),
                ];
            } else if (PERFIL === 'supervisor') {
                const mc = d.meus_checklists || {};
                const totalMc = Object.values(mc).reduce((a, b) => a + Number(b), 0);
                document.getElementById('meus-checklists').innerHTML = totalMc ? [
                    ['aprovado', 'Aprovados', 'bg-success-subtle text-success'],
                    ['pendente', 'Aguardando o administrador', 'bg-warning text-dark'],
                    ['rejeitado', 'Rejeitados (veja o motivo e corrija)', 'bg-danger']
                ].map(([k, rot, cls]) => `<div class="d-flex justify-content-between align-items-center"><span>${rot}</span><span class="badge ${cls} fs-6">${Number(mc[k] || 0)}</span></div>`).join('')
                    : '<span class="text-muted small">Você ainda não criou nenhum modelo.</span>';
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
                    labels: ['Disponível', 'Em rota', 'Manutenção', 'Locado'],
                    datasets: [{ label: 'Veículos', data: [d.frota.ativo, d.frota.em_rota, d.frota.manutencao, d.frota.locado], ...barra([COR.s1, COR.s3, COR.aviso, COR.s4]) }]
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

            // Comparativo entre equipes (admin)
            if (d.por_supervisor) {
                const ps = d.por_supervisor;
                grafico('g-supervisores', {
                    type: 'bar',
                    data: {
                        labels: ps.map(x => x.nome),
                        datasets: [
                            { label: 'Conformes', data: ps.map(x => Number(x.conforme)), ...barra(COR.conforme) },
                            { label: 'Não conformes', data: ps.map(x => Number(x.nao_conforme)), ...barra(COR.nao) }
                        ]
                    },
                    options: { indexAxis: 'y', scales: eixos(true, true), plugins: { legend: { position: 'top', align: 'end' } } }
                }, ps.length === 0);
            }
            if (d.checklists_status) {
                const cs = d.checklists_status;
                grafico('g-checklists', {
                    type: 'bar',
                    data: { labels: ['Aprovados', 'Aguardando', 'Rejeitados'], datasets: [{ label: 'Modelos', data: [Number(cs.aprovado || 0), Number(cs.pendente || 0), Number(cs.rejeitado || 0)], ...barra([COR.conforme, COR.aviso, COR.nao]) }] },
                    options: { indexAxis: 'y', scales: eixos(false, true), plugins: { legend: { display: false } } }
                }, !Object.values(cs).some(Number));
            }
            if (d.supervisores) {
                document.getElementById('tbody-supervisores').innerHTML = d.supervisores.length ? d.supervisores.map(sv => `
                    <tr>
                        <td class="fw-semibold">${esc(sv.nome)}</td>
                        <td class="text-center">${sv.motoristas}</td>
                        <td class="text-center">${Number(sv.pendentes) ? `<span class="badge bg-warning text-dark">${sv.pendentes}</span>` : '0'}</td>
                        <td class="text-center">${sv.checklists}</td>
                    </tr>`).join('') : '<tr><td colspan="4" class="text-muted small">Nenhum supervisor ativo.</td></tr>';
            }

            // Equipe do supervisor
            if (d.equipe) {
                const hoje = new Date().toISOString().slice(0, 10);
                document.getElementById('tbody-equipe').innerHTML = d.equipe.length ? d.equipe.map(m => {
                    const vencida = m.validade_cnh && m.validade_cnh < hoje;
                    return `<tr>
                        <td class="fw-semibold"><div class="d-flex align-items-center gap-2">${avatar(m.foto_url, m.nome, 30)}${esc(m.nome)}</div></td>
                        <td class="small">${m.validade_cnh ? (vencida ? `<span class="badge bg-danger"><i class="bi bi-exclamation-triangle me-1"></i>Vencida ${dataBR(m.validade_cnh)}</span>` : `${esc(m.categoria_cnh || '')} · ${dataBR(m.validade_cnh)}`) : '-'}</td>
                        <td class="small">${m.ultima_vistoria ? dataBR(m.ultima_vistoria) : '<span class="text-muted">Nenhuma</span>'}</td>
                    </tr>`;
                }).join('') : '<tr><td colspan="3" class="text-muted small">Nenhum motorista vinculado a você ainda.</td></tr>';
            }
        }

        async function carregarSolicitacoes() {
            const box = document.getElementById('lista-solicitacoes');
            if (!box) return;
            if (IS_ADMIN) {
                const jc = await getJson('backend/api.php?acao=buscar&tipo=checklists&filtro=pendente&limite=20');
                box.innerHTML = (jc.dados || []).map(c => `
                    <a href="verChecklist.html?id=${c.id_checklist}" class="list-group-item list-group-item-action d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div><div class="fw-semibold">${esc(c.titulo)}</div><div class="small text-muted">${esc(nomesCategoriasChecklist(c))} · ${c.total_perguntas} perguntas · enviado por ${esc(c.criador_nome)}</div></div>
                        <span class="btn btn-sm btn-warning fw-semibold"><i class="bi bi-patch-check me-1"></i>Revisar</span>
                    </a>`).join('') || '<div class="list-group-item text-muted small">Nenhuma pendência.</div>';
                return;
            }
            const json = await getJson('backend/api.php?acao=buscar&tipo=vistorias&filtro=minhas_pendencias&limite=50');
            const dados = json.dados || [];
            box.innerHTML = dados.length ? dados.map(v => {
                const souVist = Number(v.id_vistoriador) === MEU_ID;
                const outro = souVist ? v.nome_motorista : v.nome_vistoriador;
                const outroAssinou = souVist ? v.assinado_motorista_em : v.assinado_vistoriador_em;
                return `<a href="verVistoria.html?id=${v.id_execucao}" class="list-group-item list-group-item-action d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <div class="fw-semibold">${esc(v.placa_veiculo)} · ${esc(v.checklist_titulo)} ${badgeConformidade(v.status_conformidade)}
                            ${v.id_locacao ? `<span class="badge bg-info-subtle text-info-emphasis"><i class="bi bi-key me-1"></i>Locação · ${v.tipo_locacao === 'retirada' ? 'retirada' : 'devolução'} (você é testemunha)</span>` : ''}</div>
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


        // =====================================================================
        // LOCAÇÃO
        // =====================================================================
        const hojeISO = () => { const d = new Date(); d.setMinutes(d.getMinutes() - d.getTimezoneOffset()); return d.toISOString().slice(0, 10); };
        const somarDias = (iso, n) => { const d = new Date(iso + 'T12:00:00'); d.setDate(d.getDate() + n); return d.toISOString().slice(0, 10); };
        const qtdDias = (ini, fim) => Math.round((new Date(fim + 'T12:00:00') - new Date(ini + 'T12:00:00')) / 86400000) + 1;
        const plural = (n, um, varios) => `${n} ${n === 1 ? um : varios}`;

        function badgeFaseLocacao(l) {
            return {
                pendente: '<span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i>Aguardando aprovação</span>',
                aprovada: '<span class="badge bg-primary"><i class="bi bi-check2-circle me-1"></i>Aprovada</span>',
                aguardando_retirada: '<span class="badge bg-primary"><i class="bi bi-clipboard2-check me-1"></i>Aguardando vistoria de retirada</span>',
                em_andamento: '<span class="badge bg-success"><i class="bi bi-key me-1"></i>Em andamento</span>',
                atrasada: '<span class="badge bg-danger"><i class="bi bi-exclamation-triangle me-1"></i>Devolução atrasada</span>',
                devolvida: '<span class="badge bg-secondary-subtle text-secondary-emphasis border"><i class="bi bi-check-all me-1"></i>Devolvida</span>',
                recusada: '<span class="badge bg-dark"><i class="bi bi-x-circle me-1"></i>Recusada</span>',
                cancelada: '<span class="badge bg-light text-muted border">Cancelada</span>'
            }[l.fase] || '';
        }
        // Frase do prazo de devolução
        function prazoDevolucao(l) {
            if (l.status !== 'ativa') return '';
            const d = Number(l.dias_para_devolver);
            if (l.fase === 'aprovada') return `Retirada em ${dataBR(l.data_retirada)}`;
            if (l.fase === 'aguardando_retirada') return 'Retirada liberada: falta a vistoria';
            if (d < 0) return `<span class="text-danger fw-semibold">${plural(-d, 'dia', 'dias')} de atraso</span>`;
            if (d === 0) return '<span class="text-warning-emphasis fw-semibold">Devolver hoje</span>';
            return `Faltam ${plural(d, 'dia', 'dias')}`;
        }
        function fotoVeiculo(url, w, h) {
            return url ? `<img src="${esc(url)}" alt="" style="width:${w}px;height:${h}px;object-fit:cover;border-radius:8px">`
                : `<span class="d-inline-flex align-items-center justify-content-center bg-light border text-muted" style="width:${w}px;height:${h}px;border-radius:8px"><i class="bi bi-car-front fs-4"></i></span>`;
        }
        async function postar(acao, dados) {
            const fd = new FormData();
            Object.entries(dados).forEach(([k, v]) => fd.append(k, v ?? ''));
            fd.append('csrf', CSRF);
            const r = await fetch('backend/api.php?acao=' + acao, { method: 'POST', body: fd });
            return r.json().catch(() => ({ sucesso: false, erro: 'Não foi possível concluir. Tente de novo.' }));
        }
        function avisar(msg, tipo = 'success') {
            const main = document.querySelector('.painel-conteudo');
            main.insertAdjacentHTML('afterbegin', `<div class="alert alert-${tipo} alert-dismissible fade show shadow-sm" role="alert">${esc(msg)}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button></div>`);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Situação de uma vistoria da locação (retirada ou devolução)
        const nomeTipoVist = { retirada: 'retirada', devolucao: 'devolução' };
        function linhaVistoriaLocacao(tipo, vis, souCliente) {
            if (!vis) return `<div class="small text-muted"><i class="bi bi-circle me-1"></i>Vistoria de ${nomeTipoVist[tipo]}: ainda não feita</div>`;
            const res = vis.status_conformidade === 'nao_conforme' ? '<span class="text-danger">com avaria</span>' : '<span class="text-success">sem avaria</span>';
            const sit = {
                concluida: `<i class="bi bi-check-circle-fill text-success me-1"></i>assinada pelos dois (${res})`,
                aguardando_vistoriador: `<i class="bi bi-hourglass-split text-warning me-1"></i>aguardando ${souCliente ? 'a testemunha' : 'o supervisor'} ${esc(vis.nome_vistoriador || '')}`,
                aguardando_motorista: `<i class="bi bi-pen text-warning me-1"></i>aguardando ${souCliente ? 'a sua assinatura' : 'a assinatura do cliente'}`,
                aguardando_assinaturas: `<i class="bi bi-pen text-warning me-1"></i>aguardando ${souCliente ? 'a sua assinatura' : 'a assinatura do cliente'}`,
                recusada: souCliente ? '<i class="bi bi-tools text-primary me-1"></i>em revisão com o supervisor'
                    : `<i class="bi bi-x-circle-fill text-danger me-1"></i>recusada${vis.motivo_recusa ? ': ' + esc(vis.motivo_recusa) : ''} · refaça junto com o cliente`
            }[vis.situacao] || '';
            return `<div class="small"><a href="verVistoria.html?id=${vis.id_execucao}" class="text-decoration-none">Vistoria de ${nomeTipoVist[tipo]}</a>: ${sit}</div>`;
        }
        function blocoVistoriasLocacao(l, souCliente) {
            if (!['ativa', 'devolvida'].includes(l.status) || !l.vistorias) return '';
            const vt = l.vistorias;
            return `<div class="mt-2 p-2 rounded border bg-light">
                ${linhaVistoriaLocacao('retirada', vt.retirada, souCliente)}
                ${vt.retirada && vt.retirada.situacao === 'concluida' ? linhaVistoriaLocacao('devolucao', vt.devolucao, souCliente) : ''}
            </div>`;
        }
        // Botão principal do cliente na locação aprovada
        function acaoVistoriaCliente(l) {
            if (l.status !== 'ativa') return '';
            const vt = l.vistorias || {};
            const pendente = [vt.retirada, vt.devolucao].find(x => x && ['aguardando_motorista', 'aguardando_assinaturas'].includes(x.situacao));
            if (pendente) return `<a href="assinaturaChecklist.html?id=${pendente.id_execucao}" class="btn btn-warning fw-semibold"><i class="bi bi-pen me-1"></i>Assinar vistoria de ${nomeTipoVist[pendente.tipo_locacao]}</a>`;
            // Vistoria recusada: o cliente não precisa resolver; o supervisor refaz junto com ele
            if (l.com_supervisor) return `<div class="alert alert-info py-2 mb-0"><i class="bi bi-tools me-1"></i>Vamos resolver seu problema antes de liberar o veículo para você. O supervisor vai refazer a vistoria junto com você.</div>`;
            if (l.proxima_vistoria === 'retirada') {
                if (l.data_retirada > hojeISO()) return `<span class="btn btn-outline-secondary disabled"><i class="bi bi-calendar me-1"></i>Vistoria de retirada a partir de ${dataBR(l.data_retirada)}</span>`;
                return `<a href="cadcheck.html?locacao=${l.id_locacao}" class="btn btn-primary fw-semibold"><i class="bi bi-clipboard2-check me-1"></i>Fazer vistoria de retirada</a>`;
            }
            if (l.proxima_vistoria === 'devolucao') return `<a href="cadcheck.html?locacao=${l.id_locacao}" class="btn btn-primary fw-semibold"><i class="bi bi-clipboard2-check me-1"></i>Fazer vistoria de devolução</a>`;
            return '';
        }

        // ---------- Cliente ----------
        let minhasLocacoes = null, minhaCnh = null, disponiveis = [];

        async function buscarMinhasLocacoes() {
            const j = await getJson('backend/api.php?acao=minhas_locacoes');
            minhasLocacoes = j.dados || [];
            minhaCnh = j.cnh || {};
            return j;
        }

        function avisoCnh() {
            const venc = minhaCnh && minhaCnh.validade_cnh;
            if (!venc) return '<div class="alert alert-warning"><i class="bi bi-person-vcard me-1"></i>Sua CNH não está cadastrada. Para alugar, peça para a empresa atualizar o seu cadastro.</div>';
            if (venc < hojeISO()) return `<div class="alert alert-danger"><i class="bi bi-person-vcard me-1"></i>Sua CNH venceu em ${dataBR(venc)}. Com a CNH vencida não é possível alugar.</div>`;
            return '';
        }

        async function carregarInicioCliente() {
            const j = await buscarMinhasLocacoes();
            const atuais = minhasLocacoes.filter(l => l.status === 'ativa');
            const pendentes = minhasLocacoes.filter(l => l.status === 'pendente');
            const atual = atuais[0];
            const venc = minhaCnh.validade_cnh;
            document.getElementById('kpis').innerHTML = [
                kpi('bi-key', 'Locação atual', atual ? esc(atual.marca_modelo) : 'Nenhuma', atual ? `Placa ${esc(atual.placa)}` + (atuais.length > 1 ? ` · +${atuais.length - 1}` : '') : 'Nenhum veículo com você'),
                kpi('bi-calendar-event', 'Devolver até', atual ? dataBR(atual.data_devolucao) : '-', atual ? prazoDevolucao(atual) : '&nbsp;', atual && atual.fase === 'atrasada' ? COR.nao : null),
                kpi('bi-hourglass-split', 'Pedidos aguardando', pendentes.length, pendentes.length ? 'A empresa vai responder' : 'Nenhum pedido em análise', '#b07c00'),
                kpi('bi-car-front', 'Disponíveis agora', Number(j.disponiveis || 0), venc ? `Sua CNH vale até ${dataBR(venc)}` : 'CNH não cadastrada'),
            ].join('');

            const box = document.getElementById('cliente-locacao-atual');
            const destaque = atual || pendentes[0];
            box.innerHTML = avisoCnh() + (destaque ? cartaoLocacaoCliente(destaque) : `
                <div class="text-center py-4">
                    <i class="bi bi-car-front fs-1 text-muted"></i>
                    <p class="text-muted mb-3">Você não está com nenhum veículo agora.</p>
                    <a href="#locar" data-secao="locar" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Alugar um veículo</a>
                </div>`);
            religarLinks(box);

            const jv = await getJson('backend/api.php?acao=locacao_veiculos');
            disponiveis = jv.dados || [];
            const dest = document.getElementById('cliente-destaques');
            dest.innerHTML = disponiveis.length ? disponiveis.slice(0, 3).map(v => `
                <div class="d-flex align-items-center gap-3">
                    ${fotoVeiculo(v.foto_url, 72, 52)}
                    <div class="flex-grow-1"><div class="fw-semibold">${esc(v.marca_modelo)}</div><div class="small text-muted">${esc(v.ano)} · ${esc(v.cor || '-')}</div></div>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="abrirPedidoLocacao(${v.id_veiculo})">Alugar</button>
                </div>`).join('') : '<span class="text-muted small">Nenhum veículo livre no momento. Volte mais tarde.</span>';
        }

        function cartaoLocacaoCliente(l) {
            return `<div class="d-flex flex-wrap gap-3 align-items-start">
                ${fotoVeiculo(l.foto_url, 132, 92)}
                <div class="flex-grow-1" style="min-width:200px">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                        <span class="fw-semibold fs-5">${esc(l.marca_modelo)}</span>
                        ${l.placa ? `<span class="badge bg-dark font-monospace">${esc(l.placa)}</span>` : ''}
                        ${badgeFaseLocacao(l)}
                    </div>
                    <div class="small text-muted mb-2">${esc(l.ano)} · ${esc(l.cor || '-')}</div>
                    <div class="row g-2 small">
                        <div class="col-6 col-md-4"><div class="text-muted">Retirada</div><div class="fw-semibold">${dataBR(l.data_retirada)}</div></div>
                        <div class="col-6 col-md-4"><div class="text-muted">Devolução</div><div class="fw-semibold">${dataBR(l.data_devolucao)}</div></div>
                        <div class="col-12 col-md-4"><div class="text-muted">Período</div><div class="fw-semibold">${plural(Number(l.dias), 'dia', 'dias')}</div></div>
                    </div>
                    ${l.status === 'ativa' ? `<div class="mt-2">${prazoDevolucao(l)}</div>` : ''}
                    ${(l.fase === 'aprovada' || l.fase === 'aguardando_retirada') && !l.com_supervisor ? '<div class="small text-muted mt-1">Antes de sair com o carro, faça a vistoria de retirada. Ela comprova o estado em que você recebeu o veículo e precisa da assinatura do supervisor (testemunha).</div>' : ''}
                    ${blocoVistoriasLocacao(l, true)}
                    ${acaoVistoriaCliente(l) ? `<div class="mt-2">${acaoVistoriaCliente(l)}</div>` : ''}
                    ${l.status === 'pendente' ? '<div class="small text-muted mt-2">A empresa ainda vai aprovar o seu pedido.</div>' : ''}
                    ${l.status === 'cancelada' && l.motivo_recusa ? `<div class="alert alert-secondary small py-2 mt-2 mb-0"><strong>Cancelada pela empresa:</strong> ${esc(l.motivo_recusa)}</div>` : ''}
                    ${l.status === 'recusada' && l.motivo_recusa ? `<div class="alert alert-secondary small py-2 mt-2 mb-0"><strong>Motivo da recusa:</strong> ${esc(l.motivo_recusa)}</div>` : ''}
                    ${l.status === 'devolvida' && l.devolvido_em ? `<div class="small text-muted mt-2">Devolvido em ${dataBR(l.devolvido_em)}</div>` : ''}
                    ${l.observacao ? `<div class="small text-muted mt-1"><i class="bi bi-chat-left-text me-1"></i>${esc(l.observacao)}</div>` : ''}
                </div>
                ${l.status === 'pendente' ? `<button type="button" class="btn btn-sm btn-outline-danger" onclick="cancelarLocacao(${l.id_locacao})"><i class="bi bi-x-lg me-1"></i>Cancelar pedido</button>` : ''}
            </div>`;
        }

        async function carregarMinhasLocacoes() {
            const box = document.getElementById('lista-minhas-locacoes');
            await buscarMinhasLocacoes();
            box.innerHTML = minhasLocacoes.length ? minhasLocacoes.map(l => `<div class="cartao">${cartaoLocacaoCliente(l)}</div>`).join('')
                : `<div class="cartao text-center py-5"><i class="bi bi-calendar-x fs-1 text-muted"></i><p class="text-muted mt-2 mb-3">Você ainda não fez nenhum pedido de locação.</p>
                   <a href="#locar" data-secao="locar" class="btn btn-primary">Alugar um veículo</a></div>`;
            religarLinks(box);
        }

        async function carregarVeiculosDisponiveis() {
            const grade = document.getElementById('grade-veiculos');
            if (minhaCnh === null) await buscarMinhasLocacoes();
            document.getElementById('aviso-cnh').innerHTML = avisoCnh();
            const j = await getJson('backend/api.php?acao=locacao_veiculos');
            disponiveis = j.dados || [];
            grade.innerHTML = disponiveis.length ? disponiveis.map(v => `
                <div class="col-sm-6 col-xl-4">
                    <div class="cartao h-100 d-flex flex-column p-0 overflow-hidden">
                        ${v.foto_url ? `<img src="${esc(v.foto_url)}" alt="${esc(v.marca_modelo)}" style="width:100%;aspect-ratio:16/10;object-fit:cover">`
                            : '<div class="d-flex align-items-center justify-content-center bg-light text-muted" style="aspect-ratio:16/10"><i class="bi bi-car-front display-5"></i></div>'}
                        <div class="p-3 d-flex flex-column flex-grow-1">
                            <div class="fw-semibold fs-5">${esc(v.marca_modelo)}</div>
                            <div class="text-muted small mb-3"><i class="bi bi-calendar3 me-1"></i>${esc(v.ano)} <span class="mx-1">·</span><i class="bi bi-palette me-1"></i>${esc(v.cor || '-')}</div>
                            <span class="badge bg-success-subtle text-success border border-success-subtle align-self-start mb-3"><i class="bi bi-check-circle me-1"></i>Disponível</span>
                            <button type="button" class="btn btn-primary mt-auto" onclick="abrirPedidoLocacao(${v.id_veiculo})"><i class="bi bi-key me-1"></i>Alugar este veículo</button>
                        </div>
                    </div>
                </div>`).join('') : '<div class="col-12"><div class="cartao text-center py-5 text-muted"><i class="bi bi-car-front fs-1"></i><p class="mt-2 mb-0">Nenhum veículo disponível no momento. Volte mais tarde.</p></div></div>';
        }

        let modalLocacao;
        function atualizarDiasPedido() {
            const r = document.getElementById('locacao-retirada').value, d = document.getElementById('locacao-devolucao').value;
            document.getElementById('locacao-devolucao').min = r || hojeISO();
            document.getElementById('locacao-dias').textContent = r && d && d >= r ? `Período: ${plural(qtdDias(r, d), 'dia', 'dias')} (${dataBR(r)} a ${dataBR(d)})` : '';
        }
        function abrirPedidoLocacao(id) {
            const v = disponiveis.find(x => Number(x.id_veiculo) === Number(id));
            if (!v) return;
            document.getElementById('locacao-id-veiculo').value = v.id_veiculo;
            document.getElementById('locacao-veiculo').innerHTML = `${fotoVeiculo(v.foto_url, 96, 66)}<div><div class="fw-semibold">${esc(v.marca_modelo)}</div><div class="small text-muted">${esc(v.ano)} · ${esc(v.cor || '-')}</div></div>`;
            const hoje = hojeISO();
            const ret = document.getElementById('locacao-retirada');
            ret.min = hoje; ret.value = hoje;
            document.getElementById('locacao-devolucao').value = somarDias(hoje, 2);
            document.getElementById('locacao-obs').value = '';
            document.getElementById('locacao-erro').classList.add('d-none');
            atualizarDiasPedido();
            modalLocacao = modalLocacao || new bootstrap.Modal(document.getElementById('modal-locacao'));
            modalLocacao.show();
        }
        async function cancelarLocacao(id) {
            if (!confirm('Cancelar este pedido de locação?')) return;
            const j = await postar('cancelar_locacao', { id_locacao: id });
            avisar(j.sucesso ? j.mensagem : j.erro, j.sucesso ? 'success' : 'danger');
            carregarMinhasLocacoes(); carregarInicioCliente();
        }
        // Links internos (#locar, #locacoes) criados depois que a página carregou
        function religarLinks(raiz) {
            raiz.querySelectorAll('[data-secao]').forEach(a => a.addEventListener('click', (ev) => { ev.preventDefault(); abrirSecao(a.dataset.secao); }));
        }

        if (IS_CLIENTE) {
            document.getElementById('locacao-retirada').addEventListener('change', atualizarDiasPedido);
            document.getElementById('locacao-devolucao').addEventListener('change', atualizarDiasPedido);
            document.getElementById('form-locacao').addEventListener('submit', async (ev) => {
                ev.preventDefault();
                const btn = document.getElementById('locacao-enviar');
                const erro = document.getElementById('locacao-erro');
                btn.disabled = true;
                const j = await postar('solicitar_locacao', Object.fromEntries(new FormData(ev.target)));
                btn.disabled = false;
                if (!j.sucesso) { erro.textContent = j.erro; erro.classList.remove('d-none'); return; }
                modalLocacao.hide();
                avisar(j.mensagem);
                carregarInicioCliente();
                carregadas.locacoes = false; carregadas.locar = false;
                abrirSecao('locacoes');
            });
        }

        // ---------- Gestão (admin e supervisor) ----------
        let locacoesGestor = [];
        async function carregarLocacoesGestor() {
            const tbody = document.getElementById('tbody-locacoes');
            if (!tbody) return;
            const filtro = document.getElementById('busca-locacoes-status').value;
            const j = await getJson('backend/api.php?acao=listar_locacoes&filtro=' + encodeURIComponent(filtro));
            locacoesGestor = j.dados || [];
            const tudo = filtro ? (await getJson('backend/api.php?acao=listar_locacoes')).dados || [] : locacoesGestor;
            const cont = (f) => tudo.filter(f).length;
            document.getElementById('kpis-locacoes').innerHTML = [
                kpi('bi-hourglass-split', 'Aguardando aprovação', cont(l => l.status === 'pendente'), 'Pedidos dos clientes', '#b07c00'),
                kpi('bi-key', 'Em andamento', cont(l => ['em_andamento', 'atrasada'].includes(l.fase)), `${cont(l => ['aprovada', 'aguardando_retirada'].includes(l.fase))} aguardando retirada`),
                kpi('bi-exclamation-triangle', 'Devolução atrasada', cont(l => l.fase === 'atrasada'), 'Passaram da data combinada', COR.nao),
                kpi('bi-calendar-event', 'Devolvem hoje', cont(l => l.fase === 'em_andamento' && Number(l.dias_para_devolver) === 0), 'Prazo termina hoje'),
            ].join('');
            tbody.innerHTML = locacoesGestor.length ? locacoesGestor.map(l => `
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            ${avatar(l.cliente_foto_url, l.cliente_nome, 36)}
                            <div>
                                <a href="#" class="fw-semibold text-decoration-none" onclick="abrirFichaCliente(${l.id_cliente}); return false;" title="Ver dados completos e histórico">${esc(l.cliente_nome)} <i class="bi bi-person-vcard small"></i></a>
                                <div class="small text-muted">${esc(l.cliente_email)}${l.cliente_telefone ? ' · ' + esc(l.cliente_telefone) : ''}</div>
                                <div class="small ${l.cnh_vence_antes && l.status === 'pendente' ? 'text-danger fw-semibold' : 'text-muted'}">CNH ${esc(l.categoria_cnh || '-')} · ${l.validade_cnh ? 'vence ' + dataBR(l.validade_cnh) : 'não cadastrada'}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            ${fotoVeiculo(l.veiculo_foto_url, 64, 46)}
                            <div><div class="fw-semibold">${esc(l.marca_modelo)}</div><span class="badge bg-dark font-monospace">${esc(l.placa)}</span></div>
                        </div>
                    </td>
                    <td class="small">
                        ${badgeFaseLocacao(l)}
                        <div class="fw-semibold mt-1">${dataBR(l.data_retirada)} a ${dataBR(l.data_devolucao)} <span class="text-muted fw-normal">(${plural(Number(l.dias), 'dia', 'dias')})</span></div>
                        ${l.observacao ? `<div class="text-muted"><i class="bi bi-chat-left-text me-1"></i>${esc(l.observacao)}</div>` : ''}
                        <div class="mt-1">${prazoDevolucao(l)}</div>
                        ${l.status === 'recusada' && l.motivo_recusa ? `<div class="text-muted">Motivo: ${esc(l.motivo_recusa)}</div>` : ''}
                        ${l.avaliado_por_nome && l.status !== 'pendente' ? `<div class="text-muted">${l.status === 'recusada' ? 'Recusada' : 'Aprovada'} por ${esc(l.avaliado_por_nome)}</div>` : ''}
                        ${l.status === 'devolvida' ? `<div class="text-muted">Devolvida em ${dataBR(l.devolvido_em)}${l.km_devolucao ? ' · ' + Number(l.km_devolucao).toLocaleString('pt-BR') + ' km' : ''}</div>` : ''}
                        ${l.status === 'cancelada' && l.motivo_recusa ? `<div class="text-muted">Cancelada: ${esc(l.motivo_recusa)}</div>` : ''}
                        ${['ativa', 'devolvida'].includes(l.status) ? `<div class="text-muted">Testemunha: ${esc(l.testemunha_nome || 'sem supervisor')}</div>` : ''}
                        ${blocoVistoriasLocacao(l, false)}
                    </td>
                    <td class="text-end">
                        <div class="d-inline-flex flex-column gap-1">
                        ${l.status === 'pendente' ? `
                            <button type="button" class="btn btn-sm btn-success text-nowrap" onclick="aprovarLocacao(${l.id_locacao})"><i class="bi bi-check-lg me-1"></i>Aprovar</button>
                            <button type="button" class="btn btn-sm btn-outline-danger text-nowrap" onclick="abrirAcaoLocacao('recusar', ${l.id_locacao})"><i class="bi bi-x-lg me-1"></i>Recusar</button>` : ''}
                        ${l.status === 'ativa' && l.vistorias.devolucao && l.vistorias.devolucao.situacao === 'concluida'
                            ? `<button type="button" class="btn btn-sm btn-primary" onclick="abrirAcaoLocacao('devolver', ${l.id_locacao})"><i class="bi bi-box-arrow-in-down me-1"></i>Devolução</button>`
                            : l.status === 'ativa' && l.vistorias.retirada && l.vistorias.retirada.situacao === 'concluida'
                                ? '<span class="small text-muted" title="A devolução é registrada depois da vistoria de devolução assinada pelo cliente e pela testemunha">Aguardando vistoria de devolução</span>' : ''}
                        ${IS_SUPERVISOR && l.proxima_vistoria && (l.proxima_vistoria !== 'retirada' || l.data_retirada <= hojeISO())
                            ? `<a href="cadcheck.html?locacao=${l.id_locacao}" class="btn btn-sm ${l.vistorias[l.proxima_vistoria] ? 'btn-warning fw-semibold' : 'btn-outline-primary'} text-nowrap"
                                  title="Registrar a vistoria com o cliente presente"><i class="bi bi-clipboard2-check me-1"></i>${l.vistorias[l.proxima_vistoria] ? 'Refazer' : 'Vistoriar'} ${l.proxima_vistoria === 'retirada' ? 'retirada' : 'devolução'}</a>` : ''}
                        ${l.status === 'ativa' && !(l.vistorias.retirada && l.vistorias.retirada.situacao === 'concluida')
                            ? `<button type="button" class="btn btn-sm btn-outline-secondary" onclick="abrirAcaoLocacao('cancelar', ${l.id_locacao})" title="Cliente não retirou o veículo"><i class="bi bi-x-lg me-1"></i>Cancelar</button>` : ''}
                        </div>
                    </td>
                </tr>`).join('') : '<tr><td colspan="4" class="text-center py-4 text-muted">Nenhuma locação aqui.</td></tr>';
        }

        async function aprovarLocacao(id) {
            const l = locacoesGestor.find(x => Number(x.id_locacao) === id);
            let msg = `Aprovar a locação do ${l.marca_modelo} (${l.placa}) para ${l.cliente_nome}, de ${dataBR(l.data_retirada)} a ${dataBR(l.data_devolucao)}?`;
            if (l.cnh_vence_antes) msg += '\n\nAtenção: a CNH do cliente vence antes da devolução.';
            if (!confirm(msg)) return;
            const j = await postar('aprovar_locacao', { id_locacao: id });
            avisar(j.sucesso ? j.mensagem : j.erro, j.sucesso ? 'success' : 'danger');
            carregarLocacoesGestor();
        }

        let modalAcao, acaoAtual = null;
        function abrirAcaoLocacao(tipo, id) {
            const l = locacoesGestor.find(x => Number(x.id_locacao) === id);
            acaoAtual = { tipo, id };
            const recusar = tipo === 'recusar' || tipo === 'cancelar';
            document.getElementById('titulo-acao-locacao').innerHTML = tipo === 'recusar' ? '<i class="bi bi-x-circle me-1"></i>Recusar pedido'
                : tipo === 'cancelar' ? '<i class="bi bi-x-circle me-1"></i>Cancelar locação aprovada' : '<i class="bi bi-box-arrow-in-down me-1"></i>Registrar devolução';
            document.getElementById('acao-locacao-resumo').innerHTML = `<strong>${esc(l.marca_modelo)}</strong> (${esc(l.placa)}) · ${esc(l.cliente_nome)}<br>${dataBR(l.data_retirada)} a ${dataBR(l.data_devolucao)}`;
            const ret = l.vistorias.retirada, dev = l.vistorias.devolucao;
            const comAvaria = (x) => x && x.status_conformidade === 'nao_conforme';
            document.getElementById('acao-locacao-campo').innerHTML = recusar
                ? `<label for="acao-motivo" class="form-label fw-semibold">Motivo ${tipo === 'cancelar' ? 'do cancelamento' : 'da recusa'}</label><textarea id="acao-motivo" class="form-control" rows="3" maxlength="255" required placeholder="O cliente vai ver esta mensagem"></textarea>`
                : `<div class="row g-2 small">
                       <div class="col-6"><div class="p-2 border rounded h-100"><div class="text-muted">Retirada</div>
                           <a href="verVistoria.html?id=${ret.id_execucao}" target="_blank">Vistoria #${ret.id_execucao}</a>
                           <div class="${comAvaria(ret) ? 'text-danger' : 'text-success'} fw-semibold">${comAvaria(ret) ? 'Com avaria' : 'Sem avaria'}</div>
                           <div>${Number(ret.km_rodado).toLocaleString('pt-BR')} km</div></div></div>
                       <div class="col-6"><div class="p-2 border rounded h-100"><div class="text-muted">Devolução</div>
                           <a href="verVistoria.html?id=${dev.id_execucao}" target="_blank">Vistoria #${dev.id_execucao}</a>
                           <div class="${comAvaria(dev) ? 'text-danger' : 'text-success'} fw-semibold">${comAvaria(dev) ? 'Com avaria' : 'Sem avaria'}</div>
                           <div>${Number(dev.km_rodado).toLocaleString('pt-BR')} km (${plural(Number(dev.km_rodado) - Number(ret.km_rodado), 'km rodado', 'km rodados')})</div></div></div>
                   </div>
                   ${comAvaria(dev) && !comAvaria(ret) ? '<div class="alert alert-danger small py-2 mt-2 mb-0"><i class="bi bi-exclamation-triangle me-1"></i>O carro saiu sem avaria e voltou com avaria: a vistoria de retirada assinada comprova o estado na entrega.</div>' : ''}
                   <div class="form-text mt-2">${Number(dev.enviado_manutencao) === 1 ? 'Na aprovação da vistoria foi pedido manutenção: o veículo vai direto para a manutenção.' : 'O veículo volta a ficar disponível para locação.'}</div>`;
            const btn = document.getElementById('acao-locacao-enviar');
            btn.className = 'btn ' + (recusar ? 'btn-danger' : 'btn-primary');
            btn.innerHTML = tipo === 'recusar' ? 'Recusar pedido' : tipo === 'cancelar' ? 'Cancelar locação' : 'Confirmar devolução';
            document.getElementById('acao-locacao-erro').classList.add('d-none');
            modalAcao = modalAcao || new bootstrap.Modal(document.getElementById('modal-acao-locacao'));
            modalAcao.show();
        }
        if (IS_GESTOR) {
            document.getElementById('form-acao-locacao').addEventListener('submit', async (ev) => {
                ev.preventDefault();
                const t = acaoAtual.tipo;
                const j = t === 'devolver'
                    ? await postar('registrar_devolucao', { id_locacao: acaoAtual.id })
                    : await postar(t === 'recusar' ? 'recusar_locacao' : 'cancelar_locacao_gestor', { id_locacao: acaoAtual.id, motivo: document.getElementById('acao-motivo').value });
                if (!j.sucesso) {
                    const e = document.getElementById('acao-locacao-erro');
                    e.textContent = j.erro; e.classList.remove('d-none');
                    return;
                }
                modalAcao.hide();
                avisar(j.mensagem);
                carregarLocacoesGestor();
                carregadas.frota = false;
            });
        }


        // ---------- Clientes: cadastros pendentes e ficha completa (admin e supervisor) ----------
        let supervisoresAtivos = null;
        async function carregarClientesPendentes() {
            const card = document.getElementById('card-clientes-pendentes');
            if (!card) return;
            const j = await getJson('backend/api.php?acao=clientes_pendentes');
            const lista = j.dados || [];
            card.classList.toggle('d-none', !lista.length);
            const badgeCli = document.getElementById('badge-clientes-pendentes');
            if (badgeCli) { badgeCli.textContent = lista.length; badgeCli.classList.toggle('d-none', !lista.length); }
            if (!lista.length) document.getElementById('alerta-clientes-pendentes')?.remove();
            if (!lista.length) return;
            if (!supervisoresAtivos) supervisoresAtivos = (await getJson('backend/api.php?acao=listar_pessoas')).supervisores || [];
            const opcoes = supervisoresAtivos.map(s => `<option value="${s.id_usuario}" ${Number(s.id_usuario) === MEU_ID ? 'selected' : ''}>${esc(s.nome)}</option>`).join('');
            document.getElementById('lista-clientes-pendentes').innerHTML = lista.map(c => `
                <div class="d-flex flex-wrap align-items-center gap-2 p-2 border rounded bg-light">
                    <div class="flex-grow-1" style="min-width:220px">
                        <a href="#" class="fw-semibold text-decoration-none" onclick="abrirFichaCliente(${c.id_usuario}); return false;">${esc(c.nome)} <i class="bi bi-person-vcard small"></i></a>
                        <div class="small text-muted">${esc(c.email)} · CPF ${esc(c.cpf_formatado)} · ${esc(c.telefone || '-')}</div>
                        <div class="small text-muted">CNH ${esc(c.categoria_cnh || '-')} · vence ${dataBR(c.validade_cnh)}${c.cidade ? ' · ' + esc(c.cidade) + '/' + esc(c.estado || '') : ''} · cadastrado em ${dataBR(c.criado_em)}</div>
                    </div>
                    <select class="form-select form-select-sm" style="max-width:210px" id="sup-cli-${c.id_usuario}" aria-label="Supervisor responsável">
                        ${IS_ADMIN ? '<option value="">Supervisor responsável...</option>' : ''}${opcoes}
                    </select>
                    <button type="button" class="btn btn-sm btn-success" onclick="decidirCliente(${c.id_usuario}, true)"><i class="bi bi-check-lg me-1"></i>Aprovar</button>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="decidirCliente(${c.id_usuario}, false)"><i class="bi bi-x-lg me-1"></i>Recusar</button>
                </div>`).join('');
        }
        async function decidirCliente(id, aprovar) {
            if (!aprovar && !confirm('Recusar este cadastro? Os dados enviados pelo cliente serão apagados.')) return;
            const j = await postar(aprovar ? 'aprovar_cliente' : 'recusar_cliente', { id_usuario: id, id_supervisor: aprovar ? document.getElementById('sup-cli-' + id).value : '' });
            avisar(j.sucesso ? j.mensagem : j.erro, j.sucesso ? 'success' : 'danger');
            carregarClientesPendentes();
            carregadas.usuarios = false;
            if (document.getElementById('secao-usuarios').classList.contains('ativa')) carregarBusca('usuarios');
        }

        let modalFicha;
        async function abrirFichaCliente(id) {
            const corpo = document.getElementById('corpo-ficha-cliente');
            corpo.innerHTML = '<span class="text-muted">Carregando...</span>';
            modalFicha = modalFicha || new bootstrap.Modal(document.getElementById('modal-ficha-cliente'));
            modalFicha.show();
            const j = await getJson('backend/api.php?acao=ficha_cliente&id=' + id);
            if (!j.sucesso) { corpo.innerHTML = `<div class="alert alert-danger">${esc(j.erro || 'Não foi possível carregar.')}</div>`; return; }
            const c = j.dados, r = c.resumo;
            const campo = (rot, val) => `<div class="col-6 col-md-4"><div class="small text-muted">${rot}</div><div class="fw-semibold">${val || '-'}</div></div>`;
            const idade = c.data_nascimento ? Math.floor((Date.now() - new Date(c.data_nascimento + 'T12:00:00')) / 31557600000) : null;
            const cnhVencida = c.validade_cnh && c.validade_cnh < hojeISO();
            const endereco = [c.logradouro && `${c.logradouro}, ${c.numero || 's/n'}`, c.bairro, c.cidade && `${c.cidade}/${c.estado || ''}`, c.cep].filter(Boolean).join(' · ');
            corpo.innerHTML = `
                <div class="d-flex align-items-center gap-3 mb-3">
                    ${avatar(c.foto_url, c.nome, 56)}
                    <div>
                        <div class="fs-5 fw-semibold">${esc(c.nome)} ${c.status === 'pendente' ? '<span class="badge bg-success">Aguardando aprovação</span>' : c.status === 'inativo' ? '<span class="badge bg-secondary">Desativado</span>' : ''}</div>
                        <div class="small text-muted">Cliente desde ${dataBR(c.criado_em)} · Supervisor responsável: ${esc(c.supervisor_nome || 'não definido')}</div>
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    ${campo('CPF', esc(c.cpf_formatado))}
                    ${campo('Nascimento', c.data_nascimento ? `${dataBR(c.data_nascimento)} (${idade} anos)` : '-')}
                    ${campo('Telefone', esc(c.telefone))}
                    ${campo('E-mail', esc(c.email))}
                    ${campo('CNH', c.cnh ? `${esc(c.cnh)} · categoria ${esc(c.categoria_cnh || '-')}` : '-')}
                    ${campo('Validade da CNH', c.validade_cnh ? `<span class="${cnhVencida ? 'text-danger' : ''}">${dataBR(c.validade_cnh)}${cnhVencida ? ' (vencida)' : ''}</span>` : '-')}
                    <div class="col-12"><div class="small text-muted">Endereço</div><div class="fw-semibold">${esc(endereco) || '-'}</div></div>
                </div>
                <div class="row g-2 mb-3 text-center">
                    <div class="col-3"><div class="p-2 border rounded"><div class="fs-4 fw-bold">${r.total}</div><div class="small text-muted">locações</div></div></div>
                    <div class="col-3"><div class="p-2 border rounded"><div class="fs-4 fw-bold">${r.devolvidas}</div><div class="small text-muted">devolvidas</div></div></div>
                    <div class="col-3"><div class="p-2 border rounded ${r.com_avaria ? 'border-danger' : ''}"><div class="fs-4 fw-bold ${r.com_avaria ? 'text-danger' : ''}">${r.com_avaria}</div><div class="small text-muted">voltaram com avaria</div></div></div>
                    <div class="col-3"><div class="p-2 border rounded ${r.atrasos ? 'border-warning' : ''}"><div class="fs-4 fw-bold">${r.atrasos}</div><div class="small text-muted">com atraso</div></div></div>
                </div>
                <div class="fw-semibold mb-2">Histórico de locações</div>
                ${c.locacoes.length ? `<div class="table-responsive"><table class="table table-sm align-middle mb-0">
                    <thead><tr><th>Veículo</th><th>Período</th><th>Situação</th><th>Vistorias</th></tr></thead>
                    <tbody>${c.locacoes.map(l => `<tr>
                        <td><div class="fw-semibold">${esc(l.marca_modelo)}</div><span class="badge bg-dark font-monospace">${esc(l.placa)}</span></td>
                        <td class="small">${dataBR(l.data_retirada)} a ${dataBR(l.data_devolucao)}${l.devolveu_atrasado ? '<div class="text-warning-emphasis">devolveu com atraso</div>' : ''}</td>
                        <td class="small">${{ pendente: 'Aguardando aprovação', ativa: 'Em andamento', devolvida: 'Devolvida', recusada: 'Recusada', cancelada: 'Cancelada' }[l.status] || esc(l.status)}</td>
                        <td class="small">
                            ${l.id_vistoria_retirada ? `<a href="verVistoria.html?id=${l.id_vistoria_retirada}" target="_blank">Retirada</a> ${l.avaria_retirada ? '<span class="text-danger">(avaria)</span>' : '<span class="text-success">(ok)</span>'}` : '-'}
                            ${l.id_vistoria_devolucao ? `<br><a href="verVistoria.html?id=${l.id_vistoria_devolucao}" target="_blank">Devolução</a> ${l.avaria_devolucao ? `<span class="text-danger">(avaria${l.avaria_nova ? ' nova' : ''})</span>` : '<span class="text-success">(ok)</span>'}` : ''}
                        </td>
                    </tr>`).join('')}</tbody></table></div>` : '<div class="text-muted small">Ainda não alugou nenhum veículo.</div>'}`;
        }

        document.addEventListener('DOMContentLoaded', () => {
            if (IS_GESTOR) carregarClientesPendentes();
            if (FILTRO_INICIAL) {
                const alvo = { checklists: 'busca-checklists-cat', locacoes: 'busca-locacoes-status' }[ABA_INICIAL] || 'busca-vistorias-status';
                if (document.getElementById(alvo)) document.getElementById(alvo).value = FILTRO_INICIAL;
            }
            abrirSecao(ABA_INICIAL);
            if (IS_CLIENTE) {
                carregarInicioCliente();
                return;
            }
            carregarPainel();
            carregarSolicitacoes();
            carregarUltimasVistorias();
        });
    </script>
</body>

</html>
