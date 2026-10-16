<?php
require_once __DIR__ . '/../backend/conexao.php';

// Testa reimportação do schema.sql (para testar se DROP TABLE IF EXISTS Ocorrencias funciona)
$schema = file_get_contents(__DIR__ . '/../database/schema.sql');
$pdo->exec($schema);
echo "1. schema.sql executado com sucesso (sem erro de 'ocorrencias already exists')!\n";

// Executa seeds.sql atualizado
$seeds = file_get_contents(__DIR__ . '/../database/seeds.sql');
$pdo->exec($seeds);
echo "2. seeds.sql executado com sucesso!\n";

// Lista usuários
$stmt = $pdo->query("SELECT id_usuario, nome, email, perfil FROM Usuarios ORDER BY id_usuario");
echo "3. Usuários cadastrados no banco:\n";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "   [ID {$row['id_usuario']}] {$row['nome']} | {$row['email']} | Perfil: {$row['perfil']}\n";
}

