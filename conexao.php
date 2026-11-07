<?php
// conexao.php
$host = 'localhost';
$dbname = 'axion_db';
$usuario = 'root'; // Padrão do XAMPP
$senha = '';       // Padrão do XAMPP (vazio)

require_once __DIR__ . '/database/seed.php';

try {
    // Conecta inicialmente ao servidor MySQL e garante que o banco axion_db exista
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $usuario, $senha, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$dbname`");

    // Garante todas as tabelas e colunas atualizadas e popula automaticamente se estiver vazio
    axion_garantir_estrutura($pdo);
    $qtdUsuarios = (int)$pdo->query("SELECT COUNT(*) FROM Usuarios")->fetchColumn();
    $qtdVeiculos = (int)$pdo->query("SELECT COUNT(*) FROM Veiculos")->fetchColumn();
    if ($qtdUsuarios < 50 || $qtdVeiculos < 50) {
        axion_popular_banco($pdo, false);
    }
} catch (PDOException $e) {
    die("Erro de conexão com o banco de dados: " . $e->getMessage());
}
?>
