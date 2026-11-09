<?php
/**
 * database/seed.php
 * Gerenciador de Schema (DDL), Validação de CPF e População Automática (Seed) do banco axion_db:
 * - 1 Administrador (admin@axion.com)
 * - 13 Supervisores (incluindo supervisor@axion.com)
 * - 100 Motoristas (incluindo motorista@axion.com)
 * - 250 Clientes (incluindo cliente@axion.com)
 * - 200 Veículos (Carros, utilitários e caminhões com placas Mercosul, Renavam e Chassi)
 * - 20 Modelos de Checklists (com perguntas e subperguntas condicionais)
 * - 150 Vistorias / Checklists Preenchidos
 */

function axion_validar_cpf(string $cpf): bool {
    $num = preg_replace('/\D/', '', $cpf);
    if (strlen($num) !== 11 || preg_match('/^(\d)\1{10}$/', $num)) {
        return false;
    }
    for ($t = 9; $t < 11; $t++) {
        $soma = 0;
        for ($c = 0; $c < $t; $c++) {
            $soma += ((int)$num[$c]) * (($t + 1) - $c);
        }
        $digito = ((10 * $soma) % 11) % 10;
        if ((int)$num[$c] !== $digito) {
            return false;
        }
    }
    return true;
}

function axion_formatar_cpf_ApenasDigitosParaMascara(string $noveDigitos): string {
    $num = str_pad(preg_replace('/\D/', '', $noveDigitos), 9, '0', STR_PAD_LEFT);
    // Evita 9 dígitos iguais
    if (preg_match('/^(\d)\1{8}$/', $num)) {
        $num = substr($num, 0, 8) . (((int)$num[8] + 1) % 10);
    }
    // 1º dígito verificador
    $soma1 = 0;
    for ($i = 0; $i < 9; $i++) {
        $soma1 += ((int)$num[$i]) * (10 - $i);
    }
    $d1 = ((10 * $soma1) % 11) % 10;
    $num .= $d1;

    // 2º dígito verificador
    $soma2 = 0;
    for ($i = 0; $i < 10; $i++) {
        $soma2 += ((int)$num[$i]) * (11 - $i);
    }
    $d2 = ((10 * $soma2) % 11) % 10;
    $num .= $d2;

    return substr($num, 0, 3) . '.' . substr($num, 3, 3) . '.' . substr($num, 6, 3) . '-' . substr($num, 9, 2);
}

function axion_garantir_estrutura(PDO $pdo): void {
    // 1. Tabela Usuarios
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS Usuarios (
            id_usuario INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(150) NOT NULL,
            sobrenome VARCHAR(100) DEFAULT NULL,
            email VARCHAR(150) NOT NULL UNIQUE,
            senha VARCHAR(255) NOT NULL,
            cpf_matricula VARCHAR(20) NOT NULL UNIQUE,
            perfil VARCHAR(30) NOT NULL DEFAULT 'motorista',
            data_nascimento DATE DEFAULT NULL,
            genero VARCHAR(30) DEFAULT NULL,
            cnh VARCHAR(20) DEFAULT NULL,
            validade_cnh DATE DEFAULT NULL,
            categoria_cnh VARCHAR(5) DEFAULT NULL,
            cep VARCHAR(12) DEFAULT NULL,
            logradouro VARCHAR(180) DEFAULT NULL,
            numero VARCHAR(20) DEFAULT NULL,
            bairro VARCHAR(100) DEFAULT NULL,
            cidade VARCHAR(100) DEFAULT NULL,
            estado CHAR(2) DEFAULT NULL,
            telefone VARCHAR(25) DEFAULT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'ativo',
            criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Garante colunas extras caso a tabela Usuarios já existisse em formato antigo
    $colunasUsuarios = $pdo->query("SHOW COLUMNS FROM Usuarios")->fetchAll(PDO::FETCH_COLUMN);
    $novasColunasUsuarios = [
        'sobrenome'       => "VARCHAR(100) DEFAULT NULL AFTER nome",
        'data_nascimento' => "DATE DEFAULT NULL",
        'genero'          => "VARCHAR(30) DEFAULT NULL",
        'cnh'             => "VARCHAR(20) DEFAULT NULL",
        'validade_cnh'    => "DATE DEFAULT NULL",
        'categoria_cnh'   => "VARCHAR(5) DEFAULT NULL",
        'cep'             => "VARCHAR(12) DEFAULT NULL",
        'logradouro'      => "VARCHAR(180) DEFAULT NULL",
        'numero'          => "VARCHAR(20) DEFAULT NULL",
        'bairro'          => "VARCHAR(100) DEFAULT NULL",
        'cidade'          => "VARCHAR(100) DEFAULT NULL",
        'estado'          => "CHAR(2) DEFAULT NULL",
        'telefone'        => "VARCHAR(25) DEFAULT NULL",
        'status'          => "VARCHAR(20) NOT NULL DEFAULT 'ativo'",
        'criado_em'       => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP"
    ];
    foreach ($novasColunasUsuarios as $col => $def) {
        if (!in_array($col, $colunasUsuarios, true)) {
            $pdo->exec("ALTER TABLE Usuarios ADD COLUMN $col $def");
        }
    }
    // Garante que a coluna perfil suporte 'admin', 'supervisor', 'motorista', 'cliente'
    $pdo->exec("ALTER TABLE Usuarios MODIFY COLUMN perfil VARCHAR(30) NOT NULL DEFAULT 'motorista'");

    // 2. Tabela Veiculos
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS Veiculos (
            id_veiculo INT AUTO_INCREMENT PRIMARY KEY,
            placa VARCHAR(10) NOT NULL UNIQUE,
            marca VARCHAR(80) DEFAULT NULL,
            modelo VARCHAR(100) DEFAULT NULL,
            marca_modelo VARCHAR(180) NOT NULL,
            ano INT NOT NULL,
            cor VARCHAR(40) DEFAULT NULL,
            km_rodado INT NOT NULL DEFAULT 0,
            renavam VARCHAR(15) DEFAULT NULL,
            chassi VARCHAR(25) DEFAULT NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'ativo',
            criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $colunasVeiculos = $pdo->query("SHOW COLUMNS FROM Veiculos")->fetchAll(PDO::FETCH_COLUMN);
    $novasColunasVeiculos = [
        'marca'     => "VARCHAR(80) DEFAULT NULL AFTER placa",
        'modelo'    => "VARCHAR(100) DEFAULT NULL AFTER marca",
        'cor'       => "VARCHAR(40) DEFAULT NULL",
        'km_rodado' => "INT NOT NULL DEFAULT 0",
        'renavam'   => "VARCHAR(15) DEFAULT NULL",
        'chassi'    => "VARCHAR(25) DEFAULT NULL",
        'criado_em' => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP"
    ];
    foreach ($novasColunasVeiculos as $col => $def) {
        if (!in_array($col, $colunasVeiculos, true)) {
            $pdo->exec("ALTER TABLE Veiculos ADD COLUMN $col $def");
        }
    }

    // 3. Tabela Checklists (Modelos)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS Checklists (
            id_checklist INT AUTO_INCREMENT PRIMARY KEY,
            id_criador INT DEFAULT NULL,
            titulo VARCHAR(180) NOT NULL,
            categoria VARCHAR(50) NOT NULL DEFAULT 'multicategoria',
            descricao VARCHAR(255) DEFAULT NULL,
            ativo TINYINT(1) NOT NULL DEFAULT 1,
            criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $colunasChecklists = $pdo->query("SHOW COLUMNS FROM Checklists")->fetchAll(PDO::FETCH_COLUMN);
    foreach ([
        'descricao' => "VARCHAR(255) DEFAULT NULL",
        'ativo'     => "TINYINT(1) NOT NULL DEFAULT 1",
        'criado_em' => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP"
    ] as $col => $def) {
        if (!in_array($col, $colunasChecklists, true)) {
            $pdo->exec("ALTER TABLE Checklists ADD COLUMN $col $def");
        }
    }
    $pdo->exec("ALTER TABLE Checklists MODIFY COLUMN categoria VARCHAR(50) DEFAULT 'multicategoria'");

    // 4. Tabela Checklist_Perguntas (Com Categoria por Pergunta!)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS Checklist_Perguntas (
            id_pergunta INT AUTO_INCREMENT PRIMARY KEY,
            id_checklist INT NOT NULL,
            categoria VARCHAR(50) NOT NULL DEFAULT 'mecanico',
            id_pergunta_pai INT DEFAULT NULL,
            condicao_pai VARCHAR(20) DEFAULT NULL,
            ordem INT NOT NULL DEFAULT 1,
            texto VARCHAR(255) NOT NULL,
            tipo VARCHAR(30) NOT NULL DEFAULT 'sim_nao',
            obrigatorio TINYINT(1) NOT NULL DEFAULT 1,
            resposta_esperada VARCHAR(20) DEFAULT 'sim',
            orientacao VARCHAR(255) DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $colunasPerguntas = $pdo->query("SHOW COLUMNS FROM Checklist_Perguntas")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('categoria', $colunasPerguntas, true)) {
        $pdo->exec("ALTER TABLE Checklist_Perguntas ADD COLUMN categoria VARCHAR(50) NOT NULL DEFAULT 'mecanico' AFTER id_checklist");
        // Distribui categorias variadas nas perguntas já existentes no banco
        $pdo->exec("UPDATE Checklist_Perguntas SET categoria = 'eletrico' WHERE ordem = 1 AND id_pergunta_pai IS NULL");
        $pdo->exec("UPDATE Checklist_Perguntas SET categoria = 'mecanico' WHERE id_pergunta_pai IS NOT NULL");
        $pdo->exec("UPDATE Checklist_Perguntas SET categoria = 'estetico' WHERE ordem = 2");
        $pdo->exec("UPDATE Checklist_Perguntas SET categoria = 'mecanico' WHERE ordem = 3");
        $pdo->exec("UPDATE Checklist_Perguntas SET categoria = 'seguranca' WHERE ordem >= 4");
    }

    // 5. Tabela Checklist_Execucoes (Checklists preenchidos / Vistorias)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS Checklist_Execucoes (
            id_execucao INT AUTO_INCREMENT PRIMARY KEY,
            id_checklist INT DEFAULT NULL,
            id_veiculo INT DEFAULT NULL,
            placa_veiculo VARCHAR(10) NOT NULL,
            id_motorista INT DEFAULT NULL,
            nome_motorista VARCHAR(150) NOT NULL,
            nome_vistoriador VARCHAR(150) NOT NULL,
            data_vistoria DATE NOT NULL,
            hora_vistoria TIME NOT NULL,
            km_rodado INT NOT NULL DEFAULT 0,
            status_conformidade VARCHAR(30) NOT NULL DEFAULT 'conforme',
            observacoes TEXT DEFAULT NULL,
            criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
}

function axion_popular_banco(PDO $pdo, bool $forcarReset = false): array {
    axion_garantir_estrutura($pdo);

    if ($forcarReset) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        $pdo->exec("TRUNCATE TABLE Checklist_Execucoes");
        $pdo->exec("TRUNCATE TABLE Checklist_Perguntas");
        $pdo->exec("TRUNCATE TABLE Checklists");
        $pdo->exec("TRUNCATE TABLE Veiculos");
        $pdo->exec("TRUNCATE TABLE Usuarios");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    } else {
        $totalUsuarios = (int)$pdo->query("SELECT COUNT(*) FROM Usuarios")->fetchColumn();
        $totalVeiculos = (int)$pdo->query("SELECT COUNT(*) FROM Veiculos")->fetchColumn();
        if ($totalUsuarios >= 360 && $totalVeiculos >= 200) {
            return [
                'status' => 'ja_populado',
                'usuarios' => $totalUsuarios,
                'veiculos' => $totalVeiculos
            ];
        }
        // Limpa para garantir exatamente os quantitativos pedidos pelo professor sem duplicatas
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        $pdo->exec("TRUNCATE TABLE Checklist_Execucoes");
        $pdo->exec("TRUNCATE TABLE Checklist_Perguntas");
        $pdo->exec("TRUNCATE TABLE Checklists");
        $pdo->exec("TRUNCATE TABLE Veiculos");
        $pdo->exec("TRUNCATE TABLE Usuarios");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }

    $senhaPadraoHash = password_hash('mudar123', PASSWORD_DEFAULT);

    $nomes = [
        'Carlos', 'Roberto', 'Marcos', 'Lucas', 'Gabriel', 'Rafael', 'Daniel', 'Marcelo', 'Bruno', 'Eduardo',
        'Felipe', 'Guilherme', 'Rodrigo', 'Gustavo', 'André', 'Fernando', 'Leandro', 'Diego', 'Tiago', 'Leonardo',
        'Ana', 'Maria', 'Juliana', 'Fernanda', 'Patrícia', 'Camila', 'Aline', 'Bruna', 'Letícia', 'Vanessa',
        'Amanda', 'Mariana', 'Beatriz', 'Larissa', 'Natália', 'Carolina', 'Gabriela', 'Renata', 'Tatiane', 'Priscila'
    ];
    $sobrenomes = [
        'Silva', 'Santos', 'Oliveira', 'Souza', 'Rodrigues', 'Ferreira', 'Alves', 'Pereira', 'Lima', 'Gomes',
        'Costa', 'Ribeiro', 'Martins', 'Carvalho', 'Almeida', 'Lopes', 'Soares', 'Fernandes', 'Vieira', 'Barbosa',
        'Rocha', 'Dias', 'Nascimento', 'Andrade', 'Moreira', 'Nunes', 'Marques', 'Machado', 'Mendes', 'Freitas'
    ];
    $cidades = [
        ['cep' => '29010-001', 'logradouro' => 'Av. Jerônimo Monteiro', 'bairro' => 'Centro', 'cidade' => 'Vitória', 'uf' => 'ES', 'ddd' => '27'],
        ['cep' => '29100-010', 'logradouro' => 'Av. Champagnat', 'bairro' => 'Praia da Costa', 'cidade' => 'Vila Velha', 'uf' => 'ES', 'ddd' => '27'],
        ['cep' => '29160-790', 'logradouro' => 'Av. Eldes Scherrer Souza', 'bairro' => 'Parque Residencial Laranjeiras', 'cidade' => 'Serra', 'uf' => 'ES', 'ddd' => '27'],
        ['cep' => '29146-330', 'logradouro' => 'Av. Expedito Garcia', 'bairro' => 'Campo Grande', 'cidade' => 'Cariacica', 'uf' => 'ES', 'ddd' => '27'],
        ['cep' => '01310-100', 'logradouro' => 'Av. Paulista', 'bairro' => 'Bela Vista', 'cidade' => 'São Paulo', 'uf' => 'SP', 'ddd' => '11'],
        ['cep' => '20040-002', 'logradouro' => 'Av. Rio Branco', 'bairro' => 'Centro', 'cidade' => 'Rio de Janeiro', 'uf' => 'RJ', 'ddd' => '21'],
        ['cep' => '30140-071', 'logradouro' => 'Av. Brasil', 'bairro' => 'Funcionários', 'cidade' => 'Belo Horizonte', 'uf' => 'MG', 'ddd' => '31'],
        ['cep' => '80010-010', 'logradouro' => 'Rua XV de Novembro', 'bairro' => 'Centro', 'cidade' => 'Curitiba', 'uf' => 'PR', 'ddd' => '41']
    ];
    $categoriasCnh = ['B', 'C', 'D', 'E', 'AB', 'AC', 'AD', 'AE', 'A'];
    $generos = ['Masculino', 'Feminino'];

    $pdo->beginTransaction();

    $stmtUser = $pdo->prepare("
        INSERT INTO Usuarios (
            nome, sobrenome, email, senha, cpf_matricula, perfil,
            data_nascimento, genero, cnh, validade_cnh, categoria_cnh,
            cep, logradouro, numero, bairro, cidade, estado, telefone, status
        ) VALUES (
            :nome, :sobrenome, :email, :senha, :cpf, :perfil,
            :dt_nasc, :genero, :cnh, :val_cnh, :cat_cnh,
            :cep, :logradouro, :numero, :bairro, :cidade, :estado, :telefone, 'ativo'
        )
    ");

    $seedIndex = 100000001;
    $inserirUsuario = function (
        string $perfil,
        int $indicePerfil,
        ?string $emailFixo = null,
        ?string $nomeFixo = null,
        ?string $sobrenomeFixo = null
    ) use (
        $stmtUser, $senhaPadraoHash, $nomes, $sobrenomes, $cidades, $categoriasCnh, $generos, &$seedIndex
    ) {
        $nIdx = ($seedIndex * 7 + $indicePerfil) % count($nomes);
        $sIdx1 = ($seedIndex * 11 + $indicePerfil) % count($sobrenomes);
        $sIdx2 = ($seedIndex * 17 + $indicePerfil + 3) % count($sobrenomes);

        $primeiroNome = $nomeFixo ?? $nomes[$nIdx];
        $sobrenome = $sobrenomeFixo ?? ($sobrenomes[$sIdx1] . ' ' . $sobrenomes[$sIdx2]);
        $nomeCompleto = trim($primeiroNome . ' ' . $sobrenome);

        $cpf = axion_formatar_cpf_ApenasDigitosParaMascara((string)$seedIndex);
        $seedIndex++;

        $email = $emailFixo ?? strtolower(
            preg_replace('/[^a-zA-Z]/', '', iconv('UTF-8', 'ASCII//TRANSLIT', $primeiroNome))
            . '.' . $perfil . $indicePerfil . '@axionfrota.com.br'
        );

        $loc = $cidades[$indicePerfil % count($cidades)];
        $anoNasc = 1968 + ($indicePerfil % 34);
        $mesNasc = str_pad((string)(($indicePerfil % 12) + 1), 2, '0', STR_PAD_LEFT);
        $diaNasc = str_pad((string)(($indicePerfil % 27) + 1), 2, '0', STR_PAD_LEFT);
        $dtNasc = "$anoNasc-$mesNasc-$diaNasc";

        $genero = ($nIdx >= 20) ? 'Feminino' : 'Masculino';
        $cnh = str_pad((string)(41000000000 + $seedIndex * 13), 11, '0', STR_PAD_LEFT);
        $valCnh = (2027 + ($indicePerfil % 5)) . '-' . $mesNasc . '-' . $diaNasc;
        $catCnh = ($perfil === 'motorista')
            ? $categoriasCnh[$indicePerfil % 6] // prioriza B, C, D, E, AB, AC, AD, AE
            : $categoriasCnh[$indicePerfil % count($categoriasCnh)];

        $numero = (string)(100 + ($indicePerfil * 7) % 1800);
        $telefone = sprintf("(%s) 9%04d-%04d", $loc['ddd'], 8100 + ($indicePerfil % 1800), 1000 + (($indicePerfil * 37) % 8999));

        $stmtUser->execute([
            ':nome'       => $nomeCompleto,
            ':sobrenome'  => $sobrenome,
            ':email'      => $email,
            ':senha'      => $senhaPadraoHash,
            ':cpf'        => $cpf,
            ':perfil'     => $perfil,
            ':dt_nasc'    => $dtNasc,
            ':genero'     => $genero,
            ':cnh'        => $cnh,
            ':val_cnh'    => $valCnh,
            ':cat_cnh'    => $catCnh,
            ':cep'        => $loc['cep'],
            ':logradouro' => $loc['logradouro'],
            ':numero'     => $numero,
            ':bairro'     => $loc['bairro'],
            ':cidade'     => $loc['cidade'],
            ':estado'     => $loc['uf'],
            ':telefone'   => $telefone
        ]);
    };

    // 1. Admin (1 conta principal com privilégios máximos)
    $inserirUsuario('admin', 1, 'admin@axion.com', 'Administrador', 'Master Axion');

    // 2. Supervisores (exatamente 13 supervisores)
    for ($i = 1; $i <= 13; $i++) {
        $email = ($i === 1) ? 'supervisor@axion.com' : null;
        $nome = ($i === 1) ? 'Ricardo' : null;
        $sobre = ($i === 1) ? 'Almeida Supervisor' : null;
        $inserirUsuario('supervisor', $i, $email, $nome, $sobre);
    }

    // 3. Motoristas (exatamente 100 motoristas)
    for ($i = 1; $i <= 100; $i++) {
        $email = ($i === 1) ? 'motorista@axion.com' : null;
        $nome = ($i === 1) ? 'João' : null;
        $sobre = ($i === 1) ? 'Silva Motorista' : null;
        $inserirUsuario('motorista', $i, $email, $nome, $sobre);
    }

    // 4. Clientes (exatamente 250 clientes)
    for ($i = 1; $i <= 250; $i++) {
        $email = ($i === 1) ? 'cliente@axion.com' : null;
        $nome = ($i === 1) ? 'Fernanda' : null;
        $sobre = ($i === 1) ? 'Costa Cliente' : null;
        $inserirUsuario('cliente', $i, $email, $nome, $sobre);
    }

    // 5. Veículos (exatamente 200 carros/utilitários/caminhões)
    $modelosFrota = [
        ['Volkswagen', 'Gol 1.6 MSI'],
        ['Volkswagen', 'Saveiro Robust 1.6'],
        ['Volkswagen', 'Delivery 11.180'],
        ['Fiat', 'Strada Endurance 1.4'],
        ['Fiat', 'Fiorino Furgão 1.4'],
        ['Fiat', 'Mobi Like 1.0'],
        ['Fiat', 'Ducato Cargo 2.3'],
        ['Chevrolet', 'Onix Plus 1.0 Turbo'],
        ['Chevrolet', 'S10 Cabine Dupla 2.8'],
        ['Chevrolet', 'Montana 1.2 Turbo'],
        ['Toyota', 'Hilux SRV 4x4'],
        ['Toyota', 'Corolla XEi 2.0'],
        ['Renault', 'Master Furgão L2H2'],
        ['Renault', 'Kwid Zen 1.0'],
        ['Renault', 'Oroch Pro 1.6'],
        ['Mercedes-Benz', 'Sprinter 416 CDI'],
        ['Mercedes-Benz', 'Accelo 1016'],
        ['Volvo', 'FH 540 Globetrotter'],
        ['Volvo', 'VM 270 6x2'],
        ['Ford', 'Ranger XLS 2.0 Diesel'],
        ['Ford', 'Transit Furgão'],
        ['Hyundai', 'HB20 Sense 1.0'],
        ['Hyundai', 'HR 2.5 Diesel'],
        ['Jeep', 'Compass Longitude'],
        ['Peugeot', 'Partner Rapid 1.4']
    ];
    $cores = ['Branco', 'Prata', 'Preto', 'Cinza', 'Azul', 'Vermelho'];
    $statusVeiculos = ['ativo', 'ativo', 'ativo', 'em_rota', 'manutencao'];
    $letras = 'ABCDEFGHJKLMNPQRSTUVWXYZ';

    $stmtVeiculo = $pdo->prepare("
        INSERT INTO Veiculos (
            placa, marca, modelo, marca_modelo, ano, cor, km_rodado, renavam, chassi, status
        ) VALUES (
            :placa, :marca, :modelo, :marca_modelo, :ano, :cor, :km, :renavam, :chassi, :status
        )
    ");

    $placasGeradas = [];
    for ($v = 1; $v <= 200; $v++) {
        $par = $modelosFrota[($v - 1) % count($modelosFrota)];
        $marca = $par[0];
        $modelo = $par[1];
        $marcaModelo = "$marca $modelo";
        $ano = 2017 + ($v % 9);
        $cor = $cores[$v % count($cores)];
        $km = 12500 + ($v * 1430);
        $renavam = str_pad((string)(10000000000 + $v * 98765), 11, '0', STR_PAD_LEFT);

        $l1 = $letras[($v * 3) % strlen($letras)];
        $l2 = $letras[($v * 5 + 1) % strlen($letras)];
        $l3 = $letras[($v * 7 + 2) % strlen($letras)];
        $n1 = $v % 10;
        $l4 = $letras[($v * 11 + 4) % strlen($letras)];
        $n23 = str_pad((string)(($v * 13) % 100), 2, '0', STR_PAD_LEFT);
        $placa = ($v === 1) ? 'ABC1D23' : "{$l1}{$l2}{$l3}{$n1}{$l4}{$n23}";
        $placasGeradas[$v] = $placa;

        $chassi = sprintf("9BWZZZ377%s%s%06d", $l1, $l2, $v);
        $status = $statusVeiculos[$v % count($statusVeiculos)];

        $stmtVeiculo->execute([
            ':placa'        => $placa,
            ':marca'        => $marca,
            ':modelo'       => $modelo,
            ':marca_modelo' => $marcaModelo,
            ':ano'          => $ano,
            ':cor'          => $cor,
            ':km'           => $km,
            ':renavam'      => $renavam,
            ':chassi'       => $chassi,
            ':status'       => $status
        ]);
    }

    // 6. Modelos de Checklists (20 modelos variados + perguntas e subperguntas)
    $modelosChecklist = [
        ['Vistoria Diária de Segurança Elétrica', 'eletrico', 'Inspeção completa de faróis, setas, bateria, alternador e painel'],
        ['Checklist Mecânico de Troca de Turno', 'mecanico', 'Verificação de óleo, fluido de freio, arrefecimento e correias'],
        ['Inspeção Estética e Lataria pré-Locação', 'estetico', 'Registro de avarias externas, pintura, para-choques e vidros'],
        ['Vistoria de Pneus, Estepe e Suspensão', 'mecanico', 'Calibragem, sulco dos pneus, amortecedores e alinhamento'],
        ['Checklist de Saída para Viagem Longa', 'pre_viagem', 'Conferência de itens obrigatórios, triângulo, macaco e documentação'],
        ['Inspeção de Sistema de Freios e ABS', 'seguranca', 'Teste de pastilhas, discos, freio de estacionamento e sensores'],
        ['Checklist de Higienização e Cabine', 'estetico', 'Limpeza interna, bancos, ar-condicionado e filtros de cabine'],
        ['Vistoria de Retorno de Rota / Pós-Viagem', 'pre_viagem', 'Leitura de odômetro, consumo, ocorrências e estado geral'],
        ['Inspeção Elétrica de Iluminação Auxiliar e Giroflex', 'eletrico', 'Sinalizadores, luz de ré, luz de freio e farol de neblina'],
        ['Checklist de Transporte de Carga e Amarração', 'seguranca', 'Cintas, catracas, trava de baú, assoalho e vedação'],
        ['Auditoria de Equipamentos de Emergência e EPI', 'seguranca', 'Extintor, cones, colete refletivo, luvas e kit primeiros socorros'],
        ['Vistoria de Ar-Condicionado e Climatização', 'eletrico', 'Eficiência térmica, ruídos do compressor e ventilação'],
        ['Checklist Preventivo de Motor e Transmissão', 'mecanico', 'Vazamentos, ruídos anômalos, embreagem e câmbio'],
        ['Termo de Entrega de Veículo ao Cliente', 'estetico', 'Conferência conjunta de combustível, acessórios e estado da carroceria'],
        ['Termo de Devolução de Veículo pelo Cliente', 'estetico', 'Vistoria comparativa de devolução, km percorrido e avarias'],
        ['Checklist de Rastreador, Telemetria e Câmeras', 'eletrico', 'Sinal GPS, câmera de ré, sensor de fadiga e botão de pânico'],
        ['Inspeção de Direção Hidráulica/Elétrica', 'mecanico', 'Folgas no volante, nível de fluido e resposta da caixa de direção'],
        ['Checklist Semanal de Frotas Leves', 'outros', 'Revisão rápida de rotina para veículos administrativos e comerciais'],
        ['Checklist Semanal de Utilitários e Furgões', 'mecanico', 'Revisão estrutural de portas traseiras, corrediças e suspensão de carga'],
        ['Auditoria Mensal de Conformidade do Supervisor', 'seguranca', 'Verificação integrada de licenciamento, CNH do condutor e estado técnico']
    ];

    $stmtChecklist = $pdo->prepare("
        INSERT INTO Checklists (id_criador, titulo, categoria, descricao, ativo)
        VALUES (:id_criador, :titulo, :categoria, :descricao, 1)
    ");

    $stmtPergunta = $pdo->prepare("
        INSERT INTO Checklist_Perguntas (
            id_checklist, categoria, id_pergunta_pai, condicao_pai, ordem, texto, tipo, obrigatorio, resposta_esperada, orientacao
        ) VALUES (
            :id_checklist, :categoria, :id_pai, :condicao, :ordem, :texto, :tipo, :obrigatorio, :esperada, :orientacao
        )
    ");

    foreach ($modelosChecklist as $idx => $mod) {
        $idCriador = ($idx % 13) + 2; // Supervisores (IDs 2 a 14)
        $stmtChecklist->execute([
            ':id_criador' => $idCriador,
            ':titulo'     => $mod[0],
            ':categoria'  => 'multicategoria',
            ':descricao'  => $mod[2]
        ]);
        $idCheck = (int)$pdo->lastInsertId();

        // Pergunta 1 (Elétrica/Mecânica - Sim/Não com subpergunta condicional se responder 'nao')
        $stmtPergunta->execute([
            ':id_checklist' => $idCheck,
            ':categoria'    => 'eletrico',
            ':id_pai'       => null,
            ':condicao'     => null,
            ':ordem'        => 1,
            ':texto'        => "Os sistemas elétricos e mecânicos principais estão funcionando em perfeito estado?",
            ':tipo'         => 'sim_nao',
            ':obrigatorio'  => 1,
            ':esperada'     => 'sim',
            ':orientacao'   => 'Parar o veículo imediatamente e acionar a supervisão de manutenção.'
        ]);
        $idPergPai = (int)$pdo->lastInsertId();

        // Subpergunta 1.1 condicional (só exibe se Pergunta 1 for 'nao')
        $stmtPergunta->execute([
            ':id_checklist' => $idCheck,
            ':categoria'    => 'mecanico',
            ':id_pai'       => $idPergPai,
            ':condicao'     => 'nao',
            ':ordem'        => 1,
            ':texto'        => 'Descreva qual componente apresentou falha ou defeito:',
            ':tipo'         => 'texto',
            ':obrigatorio'  => 1,
            ':esperada'     => null,
            ':orientacao'   => 'Detalhar o defeito para abertura de Ordem de Serviço.'
        ]);

        // Pergunta 2 (Estética/Segurança - Sim/Não onde 'nao' é Conforme e 'sim' exige descrever avaria e foto!)
        $stmtPergunta->execute([
            ':id_checklist' => $idCheck,
            ':categoria'    => 'estetico',
            ':id_pai'       => null,
            ':condicao'     => null,
            ':ordem'        => 2,
            ':texto'        => 'Há alguma avaria visual na lataria, vazamento ou alerta aceso no painel?',
            ':tipo'         => 'sim_nao',
            ':obrigatorio'  => 1,
            ':esperada'     => 'nao',
            ':orientacao'   => 'Descreva a avaria encontrada e anexe a foto de evidência.'
        ]);

        // Pergunta 3 (Mecânica - Número - Quilometragem)
        $stmtPergunta->execute([
            ':id_checklist' => $idCheck,
            ':categoria'    => 'mecanico',
            ':id_pai'       => null,
            ':condicao'     => null,
            ':ordem'        => 3,
            ':texto'        => 'Informe a quilometragem atual registrada no odômetro (Km):',
            ':tipo'         => 'numero',
            ':obrigatorio'  => 1,
            ':esperada'     => null,
            ':orientacao'   => null
        ]);

        // Pergunta 4 (Segurança - Texto - Observações gerais)
        $stmtPergunta->execute([
            ':id_checklist' => $idCheck,
            ':categoria'    => 'seguranca',
            ':id_pai'       => null,
            ':condicao'     => null,
            ':ordem'        => 4,
            ':texto'        => 'Observações complementares de segurança e pré-viagem:',
            ':tipo'         => 'texto',
            ':obrigatorio'  => 0,
            ':esperada'     => null,
            ':orientacao'   => null
        ]);
    }

    // 7. Vistorias / Checklists Preenchidos (150 execuções para pesquisa e histórico rico)
    $stmtExec = $pdo->prepare("
        INSERT INTO Checklist_Execucoes (
            id_checklist, id_veiculo, placa_veiculo, id_motorista, nome_motorista,
            nome_vistoriador, data_vistoria, hora_vistoria, km_rodado, status_conformidade, observacoes
        ) VALUES (
            :id_checklist, :id_veiculo, :placa, :id_motorista, :nome_mot,
            :nome_vist, :data_vist, :hora_vist, :km, :status_conf, :obs
        )
    ");

    $observacoesExemplos = [
        'conforme'     => 'Veículo inspecionado e liberado sem pendências. Todos os itens em conformidade.',
        'nao_conforme' => 'Identificado desgaste acentuado / lâmpada queimada. Encaminhado para manutenção corretiva.',
        'pendente'     => 'Aguardando conferência final e assinatura do supervisor de turno.'
    ];

    for ($e = 1; $e <= 150; $e++) {
        $idCheck = (($e - 1) % 20) + 1;
        $idVeic = (($e * 3 - 2) % 200) + 1;
        $placa = $placasGeradas[$idVeic];
        $idMot = 15 + (($e - 1) % 100); // Motoristas têm IDs 15 a 114
        $nomeMot = $nomes[($e * 3) % count($nomes)] . ' ' . $sobrenomes[($e * 5) % count($sobrenomes)];
        $nomeVist = $nomes[($e * 7) % count($nomes)] . ' ' . $sobrenomes[($e * 2) % count($sobrenomes)] . ' (Supervisor)';

        $diasAtras = $e % 45;
        $dataVist = date('Y-m-d', strtotime("-{$diasAtras} days"));
        $horaVist = sprintf("%02d:%02d:00", 6 + ($e % 14), ($e * 7) % 60);
        $km = 15000 + ($idVeic * 1400) + ($e * 45);

        $statusConf = ($e % 7 === 0) ? 'nao_conforme' : (($e % 11 === 0) ? 'pendente' : 'conforme');
        $obs = $observacoesExemplos[$statusConf];

        $stmtExec->execute([
            ':id_checklist' => $idCheck,
            ':id_veiculo'   => $idVeic,
            ':placa'        => $placa,
            ':id_motorista' => $idMot,
            ':nome_mot'     => $nomeMot,
            ':nome_vist'    => $nomeVist,
            ':data_vist'    => $dataVist,
            ':hora_vist'    => $horaVist,
            ':km'           => $km,
            ':status_conf'  => $statusConf,
            ':obs'          => $obs
        ]);
    }

    $pdo->commit();

    return [
        'status'      => 'populado_com_sucesso',
        'admins'      => 1,
        'supervisores'=> 13,
        'motoristas'  => 100,
        'clientes'    => 250,
        'veiculos'    => 200,
        'checklists'  => 20,
        'execucoes'   => 150
    ];
}

// Se chamado diretamente via navegador (ex: database/seed.php?acao=reset)
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'seed.php' && php_sapi_name() !== 'cli') {
    session_start();
    require_once __DIR__ . '/../conexao.php';
    $forcar = isset($_GET['forcar']) && $_GET['forcar'] === '1';
    $res = axion_popular_banco($pdo, $forcar);
    header("Location: ../oqfazer.php?sucesso=banco_populado");
    exit;
}
?>
