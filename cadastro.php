<?php
// Conexão com o banco de dados
$host = 'localhost'; // ou o seu host do MySQL
$db = 'edumind';
$user = 'root'; // ou seu usuário do MySQL
$pass = ''; // ou sua senha do MySQL

$conn = new mysqli($host, $user, $pass, $db);
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

// Verificar a conexão
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

// Verificar se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Receber os dados do formulário
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $senha = $_POST['senha'];
    $confirmacao_senha = $_POST['confirmacao_senha'];
    $telefone = $_POST['telefone'];
    $cpf = $_POST['cpf'];
    $codigo_empresa = $_POST['codigo_empresa'];

    // Validar se as senhas coincidem
    if ($senha !== $confirmacao_senha) {
        echo "As senhas não coincidem!";
        exit;
    }

    // Criptografar a senha
    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

    // Preparar a consulta para inserção dos dados no banco
    $stmt = $conn->prepare("INSERT INTO usuarios (nome, email, senha, telefone, cpf, codigo_empresa) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $nome, $email, $senha_hash, $telefone, $cpf, $codigo_empresa);

    // Executar a consulta
    if ($stmt->execute()) {
        // Iniciar a sessão
        session_start();
        
        // Salvar os dados do usuário na sessão
        $_SESSION['user_id'] = $stmt->insert_id; // Usando o ID gerado do usuário
        
        // Redirecionar para a Dashboard
        header('Location: dashboard.php');
        exit;
    } else {
        echo "Erro ao cadastrar: " . $stmt->error;
    }

    // Fechar a conexão
    $stmt->close();
}

$conn->close();
?>
