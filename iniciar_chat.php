<?php
session_start();
include('db.php');

if (!isset($_SESSION['usuario_id'], $_SESSION['tipo_usuario'])) {
    header('Location: index.html'); exit;
}

$usuarioId   = $_SESSION['usuario_id'];
$tipoAtual   = $_SESSION['tipo_usuario'];

if ($tipoAtual !== 'professor') {
    // Se não for professor, redireciona para outra página
    header('Location: dashboard.php');
    exit;
}

/* lista de alunos */
$stmt = $conn->prepare(
  "SELECT id, nome FROM usuarios WHERE tipo_usuario = 'aluno'"
);
$stmt->execute();
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Iniciar Chat</title>

<link rel="stylesheet" href="style.css">
</head>
<body>

<h3>Escolha um Aluno para Iniciar o Chat</h3>
<ul>
  <?php foreach($usuarios as $u):?>
    <li>
      <a href="chat.php?id_destinatario=<?= $u['id'] ?>"><?= htmlspecialchars($u['nome'])?></a>
    </li>
  <?php endforeach; ?>
</ul>

</body>
</html>
