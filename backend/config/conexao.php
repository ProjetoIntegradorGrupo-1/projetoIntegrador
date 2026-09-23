<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$host = $_ENV['DB_HOST'];
$port = $_ENV['DB_PORT'] ?? '3306'; // Porta padrão do MySQL se não estiver definida
$database = $_ENV['DB_NAME'];
$username = $_ENV['DB_USER'];
$password = $_ENV['DB_PASSWORD'];
$caCert = $_ENV['DB_CA_CERT']; // Caminho para o ficheiro ca.pem (ex: __DIR__ . '/../ca.pem')

try {
    // DSN correto para MySQL
    $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";

    // Opções de configuração, incluindo o SSL correto para MySQL se necessário
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    // Se estiver a usar certificados SSL (como no Aiven MySQL), ative esta opção:
    if (!empty($caCert) && file_exists($caCert)) {
        $options[PDO::MYSQL_ATTR_SSL_CA] = $caCert;
        // Se a sua base de dados exigir verificação estrita do certificado:
        // $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false; 
    }

    $pdo = new PDO($dsn, $username, $password, $options);

} catch (PDOException $e) {
    die(
        "Erro ao conectar ao banco de dados MySQL." . PHP_EOL .
        "Código: " . $e->getCode() . PHP_EOL .
        "Mensagem: " . $e->getMessage()
    );
}