<?php
/**
 * backend/api.php
 * Endpoints JSON e processadores de Busca, Edição, Exclusão e Vistoria para o Sistema Axion.
 */
session_start();
require_once __DIR__ . '/../conexao.php';

$acao = $_REQUEST['acao'] ?? '';

// 1. BUSCA GERAL / FILTRADA (JSON)
if ($acao === 'buscar') {
    header('Content-Type: application/json; charset=utf-8');
    $tipo = $_GET['tipo'] ?? 'veiculos';
    $termo = trim($_GET['q'] ?? '');
    $filtroExtra = trim($_GET['filtro'] ?? '');
    $limite = min(250, max(10, (int)($_GET['limite'] ?? 100)));

    if ($tipo === 'veiculos') {
        $sql = "SELECT id_veiculo, placa, marca, modelo, marca_modelo, ano, cor, km_rodado, renavam, chassi, status
                FROM Veiculos WHERE 1=1";
        $params = [];
        if ($termo !== '') {
            $sql .= " AND (placa LIKE :q OR marca_modelo LIKE :q OR marca LIKE :q OR modelo LIKE :q OR cor LIKE :q OR renavam LIKE :q OR chassi LIKE :q)";
            $params[':q'] = "%$termo%";
        }
        if ($filtroExtra !== '') {
            $sql .= " AND status = :status";
            $params[':status'] = $filtroExtra;
        }
        $sql .= " ORDER BY id_veiculo DESC LIMIT $limite";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode(['sucesso' => true, 'dados' => $stmt->fetchAll()]);
        exit;
    }

    if ($tipo === 'usuarios') {
        $sql = "SELECT id_usuario, nome, sobrenome, email, cpf_matricula, perfil, categoria_cnh, cnh, validade_cnh,
                       cep, logradouro, numero, bairro, cidade, estado, telefone, data_nascimento, genero, status
                FROM Usuarios WHERE 1=1";
        $params = [];
        if ($termo !== '') {
            $sql .= " AND (nome LIKE :q OR email LIKE :q OR cpf_matricula LIKE :q OR cidade LIKE :q OR telefone LIKE :q OR cnh LIKE :q)";
            $params[':q'] = "%$termo%";
        }
        if ($filtroExtra !== '') {
            $sql .= " AND perfil = :perfil";
            $params[':perfil'] = $filtroExtra;
        }
        $sql .= " ORDER BY FIELD(perfil, 'admin', 'supervisor', 'motorista', 'cliente'), id_usuario ASC LIMIT $limite";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode(['sucesso' => true, 'dados' => $stmt->fetchAll()]);
        exit;
    }

    if ($tipo === 'checklists') {
        $sql = "SELECT c.id_checklist, c.titulo, c.categoria, c.descricao, c.ativo, c.criado_em,
                       COALESCE(u.nome, 'Sistema Axion') AS criador_nome,
                       (SELECT COUNT(*) FROM Checklist_Perguntas p WHERE p.id_checklist = c.id_checklist) AS total_perguntas,
                       (SELECT COUNT(*) FROM Checklist_Execucoes e WHERE e.id_checklist = c.id_checklist) AS total_execucoes
                FROM Checklists c
                LEFT JOIN Usuarios u ON u.id_usuario = c.id_criador
                WHERE 1=1";
        $params = [];
        if ($termo !== '') {
            $sql .= " AND (c.titulo LIKE :q OR c.categoria LIKE :q OR c.descricao LIKE :q)";
            $params[':q'] = "%$termo%";
        }
        if ($filtroExtra !== '') {
            $sql .= " AND c.categoria = :cat";
            $params[':cat'] = $filtroExtra;
        }
        $sql .= " ORDER BY c.id_checklist DESC LIMIT $limite";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode(['sucesso' => true, 'dados' => $stmt->fetchAll()]);
        exit;
    }

    if ($tipo === 'vistorias') {
        $sql = "SELECT e.*, COALESCE(c.titulo, 'Vistoria Geral de Frota') AS checklist_titulo,
                       COALESCE(c.categoria, 'mecanico') AS checklist_categoria
                FROM Checklist_Execucoes e
                LEFT JOIN Checklists c ON c.id_checklist = e.id_checklist
                WHERE 1=1";
        $params = [];
        if ($termo !== '') {
            $sql .= " AND (e.placa_veiculo LIKE :q OR e.nome_motorista LIKE :q OR e.nome_vistoriador LIKE :q OR c.titulo LIKE :q OR e.observacoes LIKE :q)";
            $params[':q'] = "%$termo%";
        }
        if ($filtroExtra !== '') {
            $sql .= " AND e.status_conformidade = :st";
            $params[':st'] = $filtroExtra;
        }
        $sql .= " ORDER BY e.data_vistoria DESC, e.id_execucao DESC LIMIT $limite";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode(['sucesso' => true, 'dados' => $stmt->fetchAll()]);
        exit;
    }

    echo json_encode(['sucesso' => false, 'erro' => 'Tipo de busca inválido']);
    exit;
}

// 2. OBTER DETALHE DE REGISTRO PARA EDIÇÃO / EXCLUSÃO (JSON)
if ($acao === 'obter_usuario') {
    header('Content-Type: application/json; charset=utf-8');
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $pdo->prepare("SELECT id_usuario, nome, sobrenome, email, cpf_matricula, perfil, data_nascimento, genero,
                                  cnh, validade_cnh, categoria_cnh, cep, logradouro, numero, bairro, cidade, estado, telefone, status
                           FROM Usuarios WHERE id_usuario = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $user = $stmt->fetch();
    echo json_encode(['sucesso' => (bool)$user, 'dados' => $user]);
    exit;
}

if ($acao === 'obter_veiculo') {
    header('Content-Type: application/json; charset=utf-8');
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $pdo->prepare("SELECT * FROM Veiculos WHERE id_veiculo = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $veic = $stmt->fetch();
    echo json_encode(['sucesso' => (bool)$veic, 'dados' => $veic]);
    exit;
}

if ($acao === 'obter_checklist') {
    header('Content-Type: application/json; charset=utf-8');
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $pdo->prepare("SELECT * FROM Checklists WHERE id_checklist = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $chk = $stmt->fetch();
    if ($chk) {
        $stmtP = $pdo->prepare("SELECT * FROM Checklist_Perguntas WHERE id_checklist = :id ORDER BY ordem ASC, id_pergunta ASC");
        $stmtP->execute([':id' => $id]);
        $chk['perguntas'] = $stmtP->fetchAll();
    }
    echo json_encode(['sucesso' => (bool)$chk, 'dados' => $chk]);
    exit;
}

// 3. SALVAR EDIÇÃO DE USUÁRIO
if ($acao === 'salvar_edicao_usuario' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id            = (int)($_POST['id_usuario'] ?? 0);
    $nome          = trim($_POST['nome'] ?? '');
    $sobrenome     = trim($_POST['sobrenome'] ?? '');
    $nomeCompleto  = trim($nome . ($sobrenome !== '' && stripos($nome, $sobrenome) === false ? ' ' . $sobrenome : ''));
    $cpf           = trim($_POST['cpf'] ?? '');
    $email         = trim($_POST['email'] ?? '');
    $perfil        = trim($_POST['perfil'] ?? 'motorista');
    $dtNasc        = !empty($_POST['dtNascimento']) ? $_POST['dtNascimento'] : null;
    $genero        = trim($_POST['genero'] ?? '');
    $cnh           = preg_replace('/\D/', '', $_POST['cnh'] ?? '');
    $valCnh        = !empty($_POST['validade-cnh']) ? $_POST['validade-cnh'] : null;
    $catCnh        = strtoupper(trim($_POST['categoria-cnh'] ?? 'B'));
    $cep           = trim($_POST['cep'] ?? '');
    $logradouro    = trim($_POST['logradouro'] ?? '');
    $numero        = trim($_POST['numero'] ?? '');
    $bairro        = trim($_POST['bairro'] ?? '');
    $cidade        = trim($_POST['cidade'] ?? '');
    $estado        = strtoupper(trim($_POST['estado'] ?? ''));
    $telefone      = trim($_POST['telefone'] ?? '');

    if (!axion_validar_cpf($cpf)) {
        header("Location: ../editarUsuario.html?id=$id&erro=cpf_invalido");
        exit;
    }

    $stmt = $pdo->prepare("
        UPDATE Usuarios SET
            nome = :nome, sobrenome = :sobrenome, email = :email, cpf_matricula = :cpf, perfil = :perfil,
            data_nascimento = :dt_nasc, genero = :genero, cnh = :cnh, validade_cnh = :val_cnh, categoria_cnh = :cat_cnh,
            cep = :cep, logradouro = :logradouro, numero = :numero, bairro = :bairro, cidade = :cidade, estado = :estado, telefone = :telefone
        WHERE id_usuario = :id
    ");
    $stmt->execute([
        ':nome'       => $nomeCompleto,
        ':sobrenome'  => $sobrenome,
        ':email'      => $email,
        ':cpf'        => $cpf,
        ':perfil'     => $perfil,
        ':dt_nasc'    => $dtNasc,
        ':genero'     => $genero,
        ':cnh'        => $cnh,
        ':val_cnh'    => $valCnh,
        ':cat_cnh'    => $catCnh,
        ':cep'        => $cep,
        ':logradouro' => $logradouro,
        ':numero'     => $numero,
        ':bairro'     => $bairro,
        ':cidade'     => $cidade,
        ':estado'     => $estado,
        ':telefone'   => $telefone,
        ':id'         => $id
    ]);
    header("Location: ../oqfazer.php?aba=usuarios&sucesso=usuario_editado");
    exit;
}

// 4. EXCLUIR USUÁRIO
if ($acao === 'excluir_usuario' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id_usuario'] ?? 0);
    if ($id > 0) {
        $stmt = $pdo->prepare("DELETE FROM Usuarios WHERE id_usuario = :id");
        $stmt->execute([':id' => $id]);
    }
    header("Location: ../oqfazer.php?aba=usuarios&sucesso=usuario_excluido");
    exit;
}

// 5. SALVAR EDIÇÃO DE VEÍCULO
if ($acao === 'salvar_edicao_veiculo' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id          = (int)($_POST['id_veiculo'] ?? 0);
    $marca       = trim($_POST['marca'] ?? '');
    $modelo      = trim($_POST['modelo'] ?? '');
    $marcaModelo = trim("$marca $modelo");
    $ano         = (int)($_POST['ano'] ?? date('Y'));
    $placa       = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $_POST['placa'] ?? ''));
    $cor         = trim($_POST['cor'] ?? '');
    $km          = (int)($_POST['kmrodado'] ?? 0);
    $renavam     = preg_replace('/\D/', '', $_POST['renavam'] ?? '');
    $chassi      = strtoupper(trim($_POST['chassi'] ?? ''));
    $status      = trim($_POST['status'] ?? 'ativo');

    $stmt = $pdo->prepare("
        UPDATE Veiculos SET
            placa = :placa, marca = :marca, modelo = :modelo, marca_modelo = :marca_modelo,
            ano = :ano, cor = :cor, km_rodado = :km, renavam = :renavam, chassi = :chassi, status = :status
        WHERE id_veiculo = :id
    ");
    $stmt->execute([
        ':placa'        => $placa,
        ':marca'        => $marca,
        ':modelo'       => $modelo,
        ':marca_modelo' => $marcaModelo,
        ':ano'          => $ano,
        ':cor'          => $cor,
        ':km'           => $km,
        ':renavam'      => $renavam,
        ':chassi'       => $chassi,
        ':status'       => $status,
        ':id'           => $id
    ]);
    header("Location: ../oqfazer.php?aba=veiculos&sucesso=veiculo_editado");
    exit;
}

// 6. EXCLUIR VEÍCULO
if ($acao === 'excluir_veiculo' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id_veiculo'] ?? 0);
    if ($id > 0) {
        $stmt = $pdo->prepare("DELETE FROM Veiculos WHERE id_veiculo = :id");
        $stmt->execute([':id' => $id]);
    }
    header("Location: ../oqfazer.php?aba=veiculos&sucesso=veiculo_excluido");
    exit;
}

// 7. SALVAR EDIÇÃO DE CHECKLIST
if ($acao === 'salvar_edicao_checklist' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id        = (int)($_POST['id_checklist'] ?? 0);
    $titulo    = trim($_POST['titulo'] ?? '');
    $categoria = trim($_POST['categoria'] ?? 'outros');
    $descricao = trim($_POST['descricao'] ?? '');

    if ($id > 0 && $titulo !== '') {
        $stmt = $pdo->prepare("UPDATE Checklists SET titulo = :titulo, categoria = :cat, descricao = :desc WHERE id_checklist = :id");
        $stmt->execute([':titulo' => $titulo, ':cat' => $categoria, ':desc' => $descricao, ':id' => $id]);

        if (!empty($_POST['perguntas']) && is_array($_POST['perguntas'])) {
            $pdo->prepare("DELETE FROM Checklist_Perguntas WHERE id_checklist = :id")->execute([':id' => $id]);
            $stmtP = $pdo->prepare("
                INSERT INTO Checklist_Perguntas (id_checklist, ordem, texto, tipo, obrigatorio, resposta_esperada, orientacao)
                VALUES (:id_chk, :ordem, :texto, :tipo, :obrig, :esp, :ori)
            ");
            $ordem = 1;
            foreach ($_POST['perguntas'] as $p) {
                if (empty(trim($p['texto'] ?? ''))) continue;
                $stmtP->execute([
                    ':id_chk' => $id,
                    ':ordem'  => $ordem++,
                    ':texto'  => trim($p['texto']),
                    ':tipo'   => $p['tipo'] ?? 'sim_nao',
                    ':obrig'  => isset($p['obrigatorio']) ? 1 : 0,
                    ':esp'    => $p['resposta_esperada'] ?? 'sim',
                    ':ori'    => trim($p['orientacao'] ?? '')
                ]);
            }
        }
    }
    header("Location: ../oqfazer.php?aba=checklists&sucesso=checklist_editado");
    exit;
}

// 8. EXCLUIR CHECKLIST
if ($acao === 'excluir_checklist' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id_checklist'] ?? 0);
    if ($id > 0) {
        $pdo->prepare("DELETE FROM Checklist_Perguntas WHERE id_checklist = :id")->execute([':id' => $id]);
        $pdo->prepare("DELETE FROM Checklists WHERE id_checklist = :id")->execute([':id' => $id]);
    }
    header("Location: ../oqfazer.php?aba=checklists&sucesso=checklist_excluido");
    exit;
}

// 9. REGISTRAR PREENCHIMENTO DE CHECKLIST / VISTORIA
if ($acao === 'registrar_vistoria' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $idChecklist     = !empty($_POST['id_checklist']) ? (int)$_POST['id_checklist'] : 1;
    $placa           = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $_POST['placadoveiculo'] ?? ''));
    $nomeMotorista   = trim($_POST['nomedomotorista'] ?? '');
    $nomeVistoriador = trim($_POST['nomevistoriador'] ?? '');
    $dataVistoria    = $_POST['datavistoria'] ?? date('Y-m-d');
    $horaVistoria    = $_POST['horavistoria'] ?? date('H:i:s');
    $kmRodado        = (int)($_POST['kmrodado'] ?? 0);
    $statusConf      = $_POST['status_conformidade'] ?? 'conforme';
    $observacoes     = trim($_POST['observacoes'] ?? 'Vistoria registrada via portal web.');

    // Busca id_veiculo pela placa se existir
    $stmtV = $pdo->prepare("SELECT id_veiculo FROM Veiculos WHERE placa = :placa LIMIT 1");
    $stmtV->execute([':placa' => $placa]);
    $idVeiculo = $stmtV->fetchColumn() ?: null;

    $stmt = $pdo->prepare("
        INSERT INTO Checklist_Execucoes (
            id_checklist, id_veiculo, placa_veiculo, nome_motorista, nome_vistoriador,
            data_vistoria, hora_vistoria, km_rodado, status_conformidade, observacoes
        ) VALUES (
            :id_chk, :id_veic, :placa, :mot, :vist, :dt, :hr, :km, :st, :obs
        )
    ");
    $stmt->execute([
        ':id_chk'  => $idChecklist,
        ':id_veic' => $idVeiculo,
        ':placa'   => $placa,
        ':mot'     => $nomeMotorista,
        ':vist'    => $nomeVistoriador,
        ':dt'      => $dataVistoria,
        ':hr'      => $horaVistoria,
        ':km'      => $kmRodado,
        ':st'      => $statusConf,
        ':obs'     => $observacoes
    ]);

    if ($statusConf === 'nao_conforme') {
        header("Location: ../detalhesNaoConformidade.html?placa=" . urlencode($placa));
    } else {
        header("Location: ../assinaturaChecklist.html?placa=" . urlencode($placa));
    }
    exit;
}

// 10. TROCAR PERFIL DE DEMONSTRAÇÃO (Útil para o professor avaliar abas por Nível de Acesso)
if ($acao === 'trocar_perfil_demo') {
    $novoPerfil = $_GET['perfil'] ?? 'supervisor';
    if (in_array($novoPerfil, ['admin', 'supervisor', 'motorista', 'cliente'], true)) {
        $_SESSION['perfil_usuario'] = $novoPerfil;
    }
    header("Location: ../oqfazer.php?sucesso=perfil_alterado");
    exit;
}
?>
