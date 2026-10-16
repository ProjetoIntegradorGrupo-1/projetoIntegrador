<?php
$host = 'mysql-364b144d-isabela-29db.j.aivencloud.com';
$port = 28487;
$dbname = 'defaultdb';
$user = getenv('AIVEN_DB_USER') ?: 'avnadmin';
$password = getenv('AIVEN_DB_PASS') ?: '';
$caCert = __DIR__ . '/../database/ca.pem';

echo "Tentando conectar ao Aiven MySQL...\n";

try {
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_SSL_CA => $caCert,
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false // Em PHP local no Windows evita problemas de validação de hostname estrito com CA customizada
    ];
    
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $password, $options);
    
    echo "[SUCESSO] Conexão com o Aiven MySQL estabelecida com sucesso via SSL!\n";
    
    $stmt = $pdo->query("SELECT VERSION() AS v, CURRENT_USER() AS u, DATABASE() AS d;");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Versão: " . $row['v'] . "\n";
    echo "Usuário: " . $row['u'] . "\n";
    echo "Banco: " . $row['d'] . "\n";
    
    $stmtTables = $pdo->query("SHOW TABLES;");
    $tables = $stmtTables->fetchAll(PDO::FETCH_COLUMN);
    echo "Tabelas existentes no Aiven: " . (empty($tables) ? "Nenhuma tabela criada ainda." : implode(', ', $tables)) . "\n";

} catch (PDOException $e) {
    echo "[ERRO] Falha na conexão: " . $e->getMessage() . "\n";
}

