<?php
// scratch/migrate_rbac_checklist.php
require_once __DIR__ . '/../backend/conexao.php';

try {
    echo "1. Atualizando tabela Checklists com status de governança (aprovação/ajuste)..." . PHP_EOL;

    // Altera o ENUM do campo status para suportar o workflow de aprovação
    $pdo->exec("ALTER TABLE Checklists 
                MODIFY COLUMN `status` ENUM('ativo', 'pendente_aprovacao', 'ajuste_solicitado', 'inativo') NOT NULL DEFAULT 'ativo'");

    // Adiciona colunas se não existirem
    $cols = $pdo->query("SHOW COLUMNS FROM Checklists")->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('aprovado_por', $cols)) {
        $pdo->exec("ALTER TABLE Checklists ADD COLUMN `aprovado_por` INT NULL AFTER `descricao`");
        $pdo->exec("ALTER TABLE Checklists ADD CONSTRAINT `fk_checklists_aprovador` 
                    FOREIGN KEY (`aprovado_por`) REFERENCES `Usuarios` (`id_usuario`) 
                    ON DELETE SET NULL ON UPDATE CASCADE");
        echo "Coluna 'aprovado_por' adicionada." . PHP_EOL;
    }

    if (!in_array('motivo_ajuste', $cols)) {
        $pdo->exec("ALTER TABLE Checklists ADD COLUMN `motivo_ajuste` TEXT NULL AFTER `aprovado_por`");
        echo "Coluna 'motivo_ajuste' adicionada." . PHP_EOL;
    }

    if (!in_array('data_aprovacao', $cols)) {
        $pdo->exec("ALTER TABLE Checklists ADD COLUMN `data_aprovacao` DATETIME NULL AFTER `motivo_ajuste`");
        echo "Coluna 'data_aprovacao' adicionada." . PHP_EOL;
    }

    echo "2. Verificando usuário cliente@axion.com..." . PHP_EOL;
    $stmtC = $pdo->prepare("SELECT id_usuario FROM Usuarios WHERE email = 'cliente@axion.com'");
    $stmtC->execute();
    if (!$stmtC->fetch()) {
        $senhaHash = password_hash('mudar123', PASSWORD_DEFAULT);
        $insertCliente = $pdo->prepare("INSERT INTO Usuarios 
            (nome, email, cpf_matricula, senha, perfil, telefone) 
            VALUES ('Locadora Express Cliente', 'cliente@axion.com', 'CLI-001239', :senha, 'cliente', '(27) 99888-7711')");
        $insertCliente->execute([':senha' => $senhaHash]);
        echo "Usuário cliente@axion.com cadastrado." . PHP_EOL;
    } else {
        echo "Usuário cliente@axion.com já existe." . PHP_EOL;
    }

    // Cria um checklist modelo em estado 'pendente_aprovacao' para o Gestor demonstrar na banca
    $checkPend = $pdo->prepare("SELECT id_checklist FROM Checklists WHERE status = 'pendente_aprovacao' LIMIT 1");
    $checkPend->execute();
    if (!$checkPend->fetch()) {
        $insertCheck = $pdo->prepare("INSERT INTO Checklists 
            (id_criador, titulo, categoria, descricao, status) 
            VALUES (2, 'Vistoria Preventiva de Arrefecimento e Correias', 'mecanica', 'Modelo proposto pela Supervisora Maria Oliveira para inspeção quinzenal de componentes térmicos e correias.', 'pendente_aprovacao')");
        $insertCheck->execute();
        $idNovoCheck = $pdo->lastInsertId();

        // Adiciona 2 perguntas modelo
        $insP1 = $pdo->prepare("INSERT INTO Perguntas (id_checklist, texto_pergunta, tipo_resposta, obrigatorio, categoria, ordem) VALUES (?, ?, 'sim_nao', 1, 'mecanica', 1)");
        $insP1->execute([$idNovoCheck, 'As correias dentada e do alternador apresentam ressecamento ou estrias visíveis?']);
        
        $insP2 = $pdo->prepare("INSERT INTO Perguntas (id_checklist, texto_pergunta, tipo_resposta, obrigatorio, categoria, ordem) VALUES (?, ?, 'sim_nao', 1, 'mecanica', 2)");
        $insP2->execute([$idNovoCheck, 'Tampa do reservatório de expansão veda com pressão adequada sem vazamentos?']);

        echo "Checklist modelo pendente criado com sucesso (ID: $idNovoCheck)." . PHP_EOL;
    }

    echo "Migração RBAC concluída com sucesso!" . PHP_EOL;

} catch (Exception $e) {
    echo "Erro na migração: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

