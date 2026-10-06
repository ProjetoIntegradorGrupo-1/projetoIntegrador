<?php
// frontend/caduser.php
header('Content-Type: text/html; charset=utf-8');
require_once __DIR__ . '/../backend/conexao.php';
require_once __DIR__ . '/../backend/auth_check.php';

// Apenas Gestores e Supervisores têm permissão para cadastrar usuários
autorizarAcesso(['gestor', 'supervisor']);

$perfilLogado = $_SESSION['perfil_usuario'] ?? 'supervisor';
$nomeLogado   = $_SESSION['nome_usuario'] ?? 'Operador';
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Usuários - Axion Frotas</title>
    <!-- CSS do Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .role-badge {
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }
        .section-title {
            font-size: 0.95rem;
            font-weight: 700;
            text-transform: uppercase;
            color: #6c757d;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 4px;
            margin-bottom: 16px;
        }
    </style>
</head>

<body class="bg-light">

    <div class="container py-4" style="max-width: 800px;">
        <div class="card p-4 shadow-sm border-0">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <a href="oqfazer.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Voltar ao Painel
                </a>
                <span class="badge <?php echo $perfilLogado === 'gestor' ? 'bg-danger' : 'bg-primary'; ?> role-badge px-3 py-2 text-uppercase">
                    <i class="bi <?php echo $perfilLogado === 'gestor' ? 'bi-shield-lock-fill' : 'bi-person-badge-fill'; ?>"></i>
                    Sessão: <?php echo htmlspecialchars($perfilLogado); ?> (<?php echo htmlspecialchars($nomeLogado); ?>)
                </span>
            </div>

            <h1 class="h4 text-center mb-1 fw-bold text-dark">Cadastro de Usuários</h1>
            <p class="text-muted text-center small mb-4">
                Preencha os dados cadastrais e selecione o perfil operacional para criar o acesso ao sistema.
            </p>

            <?php if ($perfilLogado === 'supervisor'): ?>
                <div class="alert alert-info py-2 px-3 small d-flex align-items-center mb-4">
                    <i class="bi bi-info-circle-fill me-2 fs-5"></i>
                    <div>
                        <strong>Regra de Governança RBAC:</strong> Como <strong>Supervisor</strong>, você possui autorização para cadastrar <strong>Motoristas / Inspetores</strong> e <strong>Clientes</strong>. O cadastro de novos Supervisores é reservado à Diretoria/Gestor.
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-success py-2 px-3 small d-flex align-items-center mb-4">
                    <i class="bi bi-shield-check me-2 fs-5"></i>
                    <div>
                        <strong>Acesso de Gestor Administrador:</strong> Você possui privilégio pleno para cadastrar <strong>Supervisores</strong>, <strong>Motoristas</strong> e <strong>Clientes</strong>.
                    </div>
                </div>
            <?php endif; ?>

            <form id="formUsuario" action="../backend/processar_cadastro.php" method="post">
                <!-- Dados Pessoais -->
                <div class="section-title">1. Dados Pessoais</div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="nome" class="form-label fw-semibold">Nome</label>
                        <input type="text" id="nome" name="nome" class="form-control" placeholder="Ex: Carlos" required autofocus>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="sobrenome" class="form-label fw-semibold">Sobrenome</label>
                        <input type="text" id="sobrenome" name="sobrenome" class="form-control" placeholder="Ex: Ferreira" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="dtNascimento" class="form-label fw-semibold">Data de Nascimento</label>
                        <input type="date" name="dtNascimento" id="dtNascimento" class="form-control" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="genero" class="form-label fw-semibold">Gênero</label>
                        <select name="genero" id="genero" class="form-select" required>
                            <option value="" selected disabled>Selecione...</option>
                            <option value="Masculino">Masculino</option>
                            <option value="Feminino">Feminino</option>
                            <option value="Outro">Outro</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="cpf" class="form-label fw-semibold">CPF</label>
                        <input type="text" id="cpf" name="cpf" class="form-control" placeholder="000.000.000-00" required>
                    </div>
                </div>

                <!-- Dados da CNH -->
                <div class="section-title mt-2">2. Habilitação Profissional (CNH)</div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="cnh" class="form-label fw-semibold">N° da CNH</label>
                        <input type="text" name="cnh" id="cnh" class="form-control" placeholder="Número do registro" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="validade-cnh" class="form-label fw-semibold">Validade da CNH</label>
                        <input type="date" name="validade-cnh" id="validade-cnh" class="form-control" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="categoria-cnh" class="form-label fw-semibold">Categoria CNH</label>
                        <input type="text" id="categoria-cnh" name="categoria-cnh" class="form-control text-uppercase" placeholder="Ex: B, D, E" required>
                    </div>
                </div>

                <!-- Endereço -->
                <div class="section-title mt-2">3. Endereço e Localização</div>
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label for="logradouro" class="form-label fw-semibold">Logradouro / Rua</label>
                        <input type="text" id="logradouro" name="logradouro" class="form-control" placeholder="Rua, Avenida, Praça..." required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="numero" class="form-label fw-semibold">Número</label>
                        <input type="text" id="numero" name="numero" class="form-control" placeholder="N° ou S/N" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="bairro" class="form-label fw-semibold">Bairro</label>
                        <input type="text" id="bairro" name="bairro" class="form-control" placeholder="Bairro" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="cidade" class="form-label fw-semibold">Cidade</label>
                        <input type="text" id="cidade" name="cidade" class="form-control" placeholder="Cidade" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="estado" class="form-label fw-semibold">Estado (UF)</label>
                        <input type="text" id="estado" name="estado" class="form-control text-uppercase" placeholder="ES, SP, RJ..." maxlength="2" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="cep" class="form-label fw-semibold">CEP</label>
                        <input type="text" id="cep" name="cep" class="form-control" placeholder="00000-000" maxlength="9" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="telefone" class="form-label fw-semibold">Telefone / Celular</label>
                        <input type="tel" id="telefone" name="telefone" class="form-control" placeholder="(00) 00000-0000" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="email" class="form-label fw-semibold">E-mail de Acesso</label>
                        <input type="email" id="email" name="email" class="form-control" placeholder="usuario@axion.com" required>
                    </div>
                </div>

                <!-- Perfil do Usuário com Controle RBAC -->
                <div class="section-title mt-2">4. Atribuição de Perfil e Salvar</div>
                <p class="text-muted small mb-2">Clique no botão do perfil correspondente para gravar o usuário com a senha padrão inicial (<code>mudar123</code>):</p>

                <div class="row g-2 mb-4">
                    <?php if ($perfilLogado === 'gestor'): ?>
                        <div class="col-md-4">
                            <button type="button" class="btn btn-outline-danger w-100 py-3 d-flex flex-column align-items-center"
                                onclick="submeterCadastro('../backend/processar_cadastro_supervisor.php')">
                                <i class="bi bi-person-gear fs-4 mb-1"></i>
                                <span class="fw-bold">Supervisor</span>
                                <small class="text-muted" style="font-size: 0.75rem;">Operação e Vistorias</small>
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="col-md-4">
                            <button type="button" class="btn btn-outline-secondary w-100 py-3 d-flex flex-column align-items-center opacity-50" disabled
                                title="Apenas Gestores Administradores podem cadastrar Supervisores">
                                <i class="bi bi-lock-fill fs-4 mb-1"></i>
                                <span class="fw-bold">Supervisor</span>
                                <small class="text-muted" style="font-size: 0.75rem;">Exclusivo do Gestor</small>
                            </button>
                        </div>
                    <?php endif; ?>

                    <div class="col-md-4">
                        <button type="button" class="btn btn-outline-primary w-100 py-3 d-flex flex-column align-items-center"
                            onclick="submeterCadastro('../backend/processar_cadastro_motorista.php')">
                            <i class="bi bi-truck fs-4 mb-1"></i>
                            <span class="fw-bold">Motorista / Inspetor</span>
                            <small class="text-muted" style="font-size: 0.75rem;">Execução em Campo</small>
                        </button>
                    </div>

                    <div class="col-md-4">
                        <button type="button" class="btn btn-outline-success w-100 py-3 d-flex flex-column align-items-center"
                            onclick="submeterCadastro('../backend/processar_cadastro_cliente.php')">
                            <i class="bi bi-person-check fs-4 mb-1"></i>
                            <span class="fw-bold">Cliente</span>
                            <small class="text-muted" style="font-size: 0.75rem;">Vistoria de Entrega/Devolução</small>
                        </button>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <a href="oqfazer.php" class="btn btn-light border">Cancelar</a>
                </div>
            </form>
        </div>
    </div>

    <!-- JS do Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function submeterCadastro(actionUrl) {
            const form = document.getElementById('formUsuario');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            form.action = actionUrl;
            form.submit();
        }
    </script>
</body>

</html>

