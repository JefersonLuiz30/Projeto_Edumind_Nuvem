<?php
$host = 'localhost';
$dbname = 'edumind';
$user = 'root';
$pass = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Ativar erros com exceções
} catch (PDOException $e) {
    echo "Erro na conexão: " . $e->getMessage();
    die();
}

?>
