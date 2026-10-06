<?php
// conexao.php
$host = 'localhost';
$dbname = 'axion_db';
$usuario = 'root'; // Padrão do XAMPP
$senha = '';       // Padrão do XAMPP (vazio)

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $usuario, $senha);
    // Configura o PDO para lançar exceções em caso de erros
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro de conexão com o banco de dados: " . $e->getMessage());
}
?>