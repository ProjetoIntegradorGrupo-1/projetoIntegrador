<?php
// backend/conexao.php

// Define fuso horário oficial do Brasil (Horário de Brasília / UTC-3) para toda a aplicação
date_default_timezone_set('America/Sao_Paulo');

// Define se a aplicação deve usar o banco local (XAMPP) ou o banco online na nuvem (Aiven)
// Altere para true ou defina a variável de ambiente USE_AIVEN=1 para conectar ao banco em nuvem da Isabela
$usar_aiven = (getenv('USE_AIVEN') === '1' || getenv('USE_AIVEN') === 'true') ? true : false; 

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
        $pdo->exec("SET time_zone = '-03:00'");
    } catch (PDOException $e) {
        die("Erro de conexão com o Aiven MySQL (Cloud): " . $e->getMessage() . 
            "<br><small>Dica: Verifique se o serviço Aiven está ativo (não pausado) e se o arquivo database/ca.pem existe.</small>");
    }

} else {
    // Configurações do MySQL Local (XAMPP ou Docker)
    $host     = getenv('DB_HOST') ?: 'localhost';
    $dbname   = getenv('DB_NAME') ?: 'axion_db';
    $usuario  = getenv('DB_USER') ?: 'root';
    $senha    = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
    $port     = getenv('DB_PORT') ?: 3306;

    try {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];
        $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
        $pdo = new PDO($dsn, $usuario, $senha, $options);
        $pdo->exec("SET time_zone = '-03:00'");
    } catch (PDOException $e) {
        die("Erro de conexão com o banco local (XAMPP/Docker): " . $e->getMessage());
    }
}
?>