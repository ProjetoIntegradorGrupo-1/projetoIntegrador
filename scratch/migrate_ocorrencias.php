<?php
require_once __DIR__ . '/../backend/conexao.php';

try {
    // 1. Cria a tabela Ocorrencias
    $sqlTable = "CREATE TABLE IF NOT EXISTS `Ocorrencias` (
        `id_ocorrencia` INT AUTO_INCREMENT PRIMARY KEY,
        `codigo_ocorrencia` VARCHAR(30) NOT NULL UNIQUE,
        `id_vistoria` INT NOT NULL,
        `id_veiculo` INT NULL,
        `placa_veiculo` VARCHAR(10) NOT NULL,
        `modelo_veiculo` VARCHAR(100) NOT NULL,
        `subsistema` VARCHAR(50) NOT NULL DEFAULT 'Geral',
        `descricao_falha` TEXT NOT NULL,
        `criticidade` ENUM('baixa', 'media', 'alta', 'critica') NOT NULL DEFAULT 'media',
        `status` ENUM('aberta', 'em_oficina', 'resolvida') NOT NULL DEFAULT 'aberta',
        `status_veiculo` ENUM('retido_oficina', 'liberado') NOT NULL DEFAULT 'retido_oficina',
        `acao_recomendada` TEXT NULL,
        `foto_evidencia` VARCHAR(255) NULL,
        `local_patio` VARCHAR(100) DEFAULT 'Pátio Principal',
        `fiscal_responsavel` VARCHAR(150) NOT NULL,
        `data_abertura` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `data_resolucao` DATETIME NULL,
        `observacao_despacho` TEXT NULL,
        `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_ocorrencias_status` (`status`),
        INDEX `idx_ocorrencias_criticidade` (`criticidade`),
        INDEX `idx_ocorrencias_placa` (`placa_veiculo`),
        CONSTRAINT `fk_ocorrencias_vistoria` 
            FOREIGN KEY (`id_vistoria`) REFERENCES `Vistorias` (`id_vistoria`) 
            ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT `fk_ocorrencias_veiculo` 
            FOREIGN KEY (`id_veiculo`) REFERENCES `Veiculos` (`id_veiculo`) 
            ON DELETE SET NULL ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $pdo->exec($sqlTable);
    echo "Tabela Ocorrencias criada com sucesso!\n";

    // 2. Insere ou sincroniza ocorrências reais baseadas nas vistorias existentes
    // Pega as vistorias com avarias
    $stmtV = $pdo->query("SELECT v.*, (SELECT caminho_arquivo FROM EvidenciasVistoria e WHERE e.id_vistoria = v.id_vistoria LIMIT 1) as foto FROM Vistorias v WHERE v.status = 'aprovado_com_restricoes' ORDER BY v.id_vistoria ASC");
    $vistoriasComAvaria = $stmtV->fetchAll(PDO::FETCH_ASSOC);

    // Lista de ocorrências modelo conforme Figura 2 da Profa. Marta
    $dadosExemplo = [
        [
            'codigo' => 'OC-2026-089',
            'modelo' => 'Fiat Strada Volcano',
            'placa' => 'ABC-1D23',
            'subsistema' => 'Pneus e Rodas',
            'falha' => 'Pneu dianteiro direito com arame aparente e corte lateral',
            'criticidade' => 'critica',
            'status' => 'aberta',
            'status_veiculo' => 'retido_oficina',
            'acao' => 'Veículo impedido de circular. Necessário envio imediato para borracharia credenciada.',
            'local' => 'GPS Pátio Sul',
            'fiscal' => 'João da Silva'
        ],
        [
            'codigo' => 'OC-2026-090',
            'modelo' => 'Toyota Hilux 4x4',
            'placa' => 'OPQ-4R56',
            'subsistema' => 'Sistema de Freios',
            'falha' => 'Vazamento ativo de fluido de freio na pinça traseira esquerda',
            'criticidade' => 'critica',
            'status' => 'em_oficina',
            'status_veiculo' => 'retido_oficina',
            'acao' => 'Veículo retido na oficina mecânica autorizada para substituição das vedações da pinça.',
            'local' => 'Oficina Mecânica Norte',
            'fiscal' => 'Maria Oliveira'
        ],
        [
            'codigo' => 'OC-2026-091',
            'modelo' => 'Fiat Toro Ranch',
            'placa' => 'KLM-1N23',
            'subsistema' => 'Iluminação e Elétrica',
            'falha' => 'Farol de milha direito com lente trincada por impacto de pedra',
            'criticidade' => 'media',
            'status' => 'aberta',
            'status_veiculo' => 'liberado',
            'acao' => 'Veículo liberado com restrição. Agendar substituição do conjunto óptico em até 5 dias úteis.',
            'local' => 'Pátio Central',
            'fiscal' => 'Carlos Mendes'
        ],
        [
            'codigo' => 'OC-2026-092',
            'modelo' => 'Chevrolet Onix Plus',
            'placa' => 'GHI-9J01',
            'subsistema' => 'Fluidos e Visibilidade',
            'falha' => 'Nível de água do limpador de para-brisa abaixo do mínimo',
            'criticidade' => 'baixa',
            'status' => 'resolvida',
            'status_veiculo' => 'liberado',
            'acao' => 'Reservatório reabastecido no local pelo inspetor. Ocorrência sanada.',
            'local' => 'Pátio Sul',
            'fiscal' => 'João da Silva'
        ]
    ];

    $stmtCheckCount = $pdo->query("SELECT COUNT(*) FROM Ocorrencias");
    if ($stmtCheckCount->fetchColumn() == 0) {
        $stmtInsert = $pdo->prepare("INSERT INTO Ocorrencias 
            (codigo_ocorrencia, id_vistoria, id_veiculo, placa_veiculo, modelo_veiculo, subsistema, descricao_falha, criticidade, status, status_veiculo, acao_recomendada, foto_evidencia, local_patio, fiscal_responsavel)
            VALUES (:cod, :id_v, :id_vei, :placa, :mod, :sub, :falha, :crit, :st, :st_vei, :acao, :foto, :local, :fiscal)");

        // Se houver fotos reais na pasta de uploads, aproveita para vincular
        $fotos = glob(__DIR__ . '/../backend/uploads/evidencias/*.jpg');

        foreach ($dadosExemplo as $idx => $d) {
            $idVistoriaRef = isset($vistoriasComAvaria[$idx]) ? $vistoriasComAvaria[$idx]['id_vistoria'] : 1;
            $fotoVinculada = !empty($fotos[$idx]) ? 'uploads/evidencias/' . basename($fotos[$idx]) : ($vistoriasComAvaria[0]['foto'] ?? null);

            $stmtInsert->execute([
                ':cod' => $d['codigo'],
                ':id_v' => $idVistoriaRef,
                ':id_vei' => 1,
                ':placa' => $d['placa'],
                ':mod' => $d['modelo'],
                ':sub' => $d['subsistema'],
                ':falha' => $d['falha'],
                ':crit' => $d['criticidade'],
                ':st' => $d['status'],
                ':st_vei' => $d['status_veiculo'],
                ':acao' => $d['acao'],
                ':foto' => $fotoVinculada,
                ':local' => $d['local'],
                ':fiscal' => $d['fiscal']
            ]);
        }
        echo "4 Ocorrências de demonstração inseridas com sucesso!\n";
    } else {
        echo "Tabela Ocorrencias já possui registros.\n";
    }

} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}

