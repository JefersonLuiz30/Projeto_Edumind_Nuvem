<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

$erro = '';

// Conexão com o banco de dados
$host = 'localhost';
$db = 'edumind';
$user = 'root';
$pass = '';
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Falha na conexão: " . htmlspecialchars($conn->connect_error));
}

// Verificar login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['email']) && !empty($_POST['senha'])) {
        $email = $_POST['email'];
        $senha = $_POST['senha'];

        // Buscar usuário por email
        $stmt = $conn->prepare("SELECT id, senha, tipo_usuario FROM usuarios WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $senha_hash, $tipo_usuario);
            $stmt->fetch();

            if (password_verify($senha, $senha_hash)) {
                // Login bem-sucedido
                $_SESSION['usuario_id'] = $id;
                $_SESSION['tipo_usuario'] = $tipo_usuario;

                // Reforça segurança da sessão
                session_regenerate_id(true);

                // Redireciona para a dashboard
                header("Location: dashboard.php");
                exit;
            } else {
                $erro = "Senha incorreta!";
            }
        } else {
            $erro = "Usuário não encontrado!";
        }

        $stmt->close();
    } else {
        $erro = "Preencha todos os campos.";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Login - EduMind</title>
</head>
<body>
    <h2>Login</h2>

    <?php if (!empty($erro)): ?>
        <p style="color:red;"><?php echo htmlspecialchars($erro); ?></p>
    <?php endif; ?>

    <form method="post" action="login.php">
        <label>Email:</label><br>
        <input type="email" name="email" required><br><br>

        <label>Senha:</label><br>
        <input type="password" name="senha" required><br><br>

        <button type="submit">Entrar</button>
    </form>

    <p>Não tem uma conta? <a href="cadastro.php">Cadastre-se</a></p>

    <script src="auth.js"></script>
</body>
</html>
