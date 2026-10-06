<?php
header('Content-Type: text/html; charset=utf-8');
require_once __DIR__ . '/../backend/conexao.php';
require_once __DIR__ . '/../backend/auth_check.php';

// Edição de cadastro de usuários é restrita ao Gestor Administrador
autorizarAcesso(['gestor']);


// Busca lista de todos os usuários ativos para o select
$stmtTodos = $pdo->query("SELECT id_usuario, nome, cpf_matricula, perfil FROM Usuarios WHERE ativo = 1 ORDER BY nome ASC");
$listaUsuarios = $stmtTodos->fetchAll(PDO::FETCH_ASSOC);

// Identifica o usuário a ser editado
$id_selecionado = intval($_GET['id_usuario'] ?? 0);
$usuarioAtual = null;

if ($id_selecionado > 0) {
    $stmtUser = $pdo->prepare("SELECT * FROM Usuarios WHERE id_usuario = :id LIMIT 1");
    $stmtUser->bindParam(':id', $id_selecionado, PDO::PARAM_INT);
    $stmtUser->execute();
    $usuarioAtual = $stmtUser->fetch(PDO::FETCH_ASSOC);
} elseif (count($listaUsuarios) > 0) {
    $id_selecionado = $listaUsuarios[0]['id_usuario'];
    $stmtUser = $pdo->prepare("SELECT * FROM Usuarios WHERE id_usuario = :id LIMIT 1");
    $stmtUser->bindParam(':id', $id_selecionado, PDO::PARAM_INT);
    $stmtUser->execute();
    $usuarioAtual = $stmtUser->fetch(PDO::FETCH_ASSOC);
}

// Separa primeiro nome e sobrenome se aplicável
$partesNome = explode(' ', $usuarioAtual['nome'] ?? '', 2);
$primeiroNome = $partesNome[0] ?? '';
$sobrenome = $partesNome[1] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuário - Axion</title>
    <!-- CSS do Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

    <div class="container d-flex justify-content-center align-items-center min-vh-100 py-4">
        <div class="card p-4 shadow-sm" style="max-width: 750px; width: 100%;">
            <h1 class="h4 text-center mb-4">Editar Usuário</h1>

            <!-- 1. BLOCO DE BUSCA DINÂMICO -->
            <form action="editarUsuario.php" method="get" class="mb-4 p-3 bg-light border rounded">
                <label for="select-usuario" class="form-label fw-bold">Selecione o Usuário para Editar</label>
                <div class="input-group">
                    <select id="select-usuario" name="id_usuario" class="form-select" onchange="this.form.submit()" required>
                        <?php if (empty($listaUsuarios)): ?>
                            <option value="" disabled selected>Nenhum usuário cadastrado</option>
                        <?php else: ?>
                            <?php foreach ($listaUsuarios as $u): ?>
                                <option value="<?= $u['id_usuario'] ?>" <?= ($u['id_usuario'] == $id_selecionado) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($u['nome']) ?> (CPF: <?= htmlspecialchars($u['cpf_matricula']) ?>) - <?= ucfirst(htmlspecialchars($u['perfil'])) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <button class="btn btn-outline-primary" type="submit">Carregar Dados</button>
                </div>
            </form>

            <hr class="my-4">

            <?php if ($usuarioAtual): ?>
            <!-- 2. FORMULÁRIO DE EDIÇÃO PREENCHIDO COM DADOS REAIS DO BANCO -->
            <form action="../backend/processar_edicao_usuario.php" method="post">
                <input type="hidden" name="id_usuario" value="<?= $usuarioAtual['id_usuario'] ?>">

                <!-- Dados Pessoais -->
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="nome" class="form-label">Nome</label>
                        <input type="text" id="nome" name="nome" class="form-control" value="<?= htmlspecialchars($primeiroNome) ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="sobrenome" class="form-label">Sobrenome</label>
                        <input type="text" id="sobrenome" name="sobrenome" class="form-control" value="<?= htmlspecialchars($sobrenome) ?>" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="dtNascimento" class="form-label">Data de Nascimento</label>
                        <input type="date" name="dtNascimento" id="dtNascimento" class="form-control" value="<?= htmlspecialchars($usuarioAtual['data_nascimento'] ?? '') ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="genero" class="form-label">Gênero</label>
                        <select name="genero" id="genero" class="form-select">
                            <option value="" <?= empty($usuarioAtual['genero']) ? 'selected' : '' ?>>Selecione...</option>
                            <option value="Masculino" <?= (($usuarioAtual['genero'] ?? '') === 'Masculino') ? 'selected' : '' ?>>Masculino</option>
                            <option value="Feminino" <?= (($usuarioAtual['genero'] ?? '') === 'Feminino') ? 'selected' : '' ?>>Feminino</option>
                            <option value="Outro" <?= (($usuarioAtual['genero'] ?? '') === 'Outro') ? 'selected' : '' ?>>Outro</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="cpf" class="form-label">CPF</label>
                        <input type="text" id="cpf" name="cpf" class="form-control" value="<?= htmlspecialchars($usuarioAtual['cpf_matricula'] ?? '') ?>" required>
                    </div>
                </div>

                <!-- CNH -->
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="cnh" class="form-label">N° da CNH</label>
                        <input type="text" name="cnh" id="cnh" class="form-control" value="<?= htmlspecialchars($usuarioAtual['cnh'] ?? '') ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="validade-cnh" class="form-label">Validade CNH</label>
                        <input type="date" name="validade-cnh" id="validade-cnh" class="form-control" value="<?= htmlspecialchars($usuarioAtual['validade_cnh'] ?? '') ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="categoria-cnh" class="form-label">Categoria CNH</label>
                        <input type="text" id="categoria-cnh" name="categoria-cnh" class="form-control text-uppercase" value="<?= htmlspecialchars($usuarioAtual['categoria_cnh'] ?? '') ?>">
                    </div>
                </div>

                <!-- Contato e Perfil -->
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="telefone" class="form-label">Telefone</label>
                        <input type="tel" id="telefone" name="telefone" class="form-control" value="<?= htmlspecialchars($usuarioAtual['telefone'] ?? '') ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="email" class="form-label">E-mail</label>
                        <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($usuarioAtual['email'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="perfil" class="form-label">Perfil de Acesso</label>
                        <select name="perfil" id="perfil" class="form-select" required>
                            <option value="gestor" <?= (($usuarioAtual['perfil'] ?? '') === 'gestor') ? 'selected' : '' ?>>Gestor / Admin</option>
                            <option value="supervisor" <?= (($usuarioAtual['perfil'] ?? '') === 'supervisor') ? 'selected' : '' ?>>Supervisor</option>
                            <option value="motorista" <?= (($usuarioAtual['perfil'] ?? '') === 'motorista') ? 'selected' : '' ?>>Motorista</option>
                            <option value="cliente" <?= (($usuarioAtual['perfil'] ?? '') === 'cliente') ? 'selected' : '' ?>>Cliente</option>
                        </select>
                    </div>
                </div>

                <!-- Botões de Ação -->
                <div class="d-grid gap-2 mt-4">
                    <input type="submit" value="Salvar Alterações" class="btn btn-primary">
                    <a href="oqfazer.php" class="btn btn-outline-secondary">Cancelar e Voltar</a>
                </div>
            </form>
            <?php else: ?>
                <div class="alert alert-info text-center">Nenhum usuário cadastrado para edição.</div>
                <div class="d-grid">
                    <a href="oqfazer.php" class="btn btn-outline-secondary">Voltar ao Menu</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- JS do Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

