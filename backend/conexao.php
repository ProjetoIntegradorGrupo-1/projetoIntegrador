<?php
// backend/conexao.php

// Define se a aplicação deve usar o banco local (XAMPP) ou o banco online na nuvem (Aiven)
// Altere para true para conectar ao banco em nuvem da Isabela
$usar_aiven = false; 

if ($usar_aiven) {
    // Configurações do Aiven MySQL (Cloud) - carregadas via variável de ambiente
    $host     = getenv('AIVEN_DB_HOST') ?: 'mysql-364b144d-isabela-29db.j.aivencloud.com';
    $port     = getenv('AIVEN_DB_PORT') ?: 28487;
    $dbname   = getenv('AIVEN_DB_NAME') ?: 'defaultdb';
    $usuario  = getenv('AIVEN_DB_USER') ?: 'avnadmin';
    $senha    = getenv('AIVEN_DB_PASS') ?: '';
    $ca_cert  = __DIR__ . '/../database/ca.pem';


    try {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_SSL_CA => $ca_cert,
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];
        $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
        $pdo = new PDO($dsn, $usuario, $senha, $options);
    } catch (PDOException $e) {
        die("Erro de conexão com o Aiven MySQL (Cloud): " . $e->getMessage() . 
            "<br><small>Dica: Verifique se o serviço Aiven está ativo (não pausado) e se o arquivo database/ca.pem existe.</small>");
    }

} else {
    // Configurações do MySQL Local (XAMPP)
    $host     = 'localhost';
    $dbname   = 'axion_db';
    $usuario  = 'root';
    $senha    = '';

    try {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];
        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
        $pdo = new PDO($dsn, $usuario, $senha, $options);
    } catch (PDOException $e) {
        die("Erro de conexão com o banco local (XAMPP): " . $e->getMessage());
    }
}
?>