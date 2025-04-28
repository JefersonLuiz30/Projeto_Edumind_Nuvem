<?php
session_start();
include('db.php');

if (!isset($_SESSION['usuario_id'], $_GET['aluno_id'])) {
    header('Location: index.html'); exit;
}

$usuarioId      = $_SESSION['usuario_id'];
$alunoId        = $_GET['aluno_id'];
$tipoAtual      = $_SESSION['tipo_usuario'];

if ($tipoAtual !== 'professor') {
    // Se não for professor, redireciona para outra página
    header('Location: dashboard.php');
    exit;
}

/* Verifica se o aluno existe */
$stmt = $conn->prepare("SELECT nome FROM usuarios WHERE id = :id AND tipo_usuario = 'aluno'");
$stmt->bindParam(':id', $alunoId);
$stmt->execute();
$aluno = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$aluno) {
    header('Location: iniciar_chat.php'); exit; // Se o aluno não for encontrado, redireciona para a página de iniciar chat
}

$alunoNome = $aluno['nome'];

/* Carrega as mensagens */
$stmt = $conn->prepare(
    "SELECT u.nome, m.mensagem, m.data_envio 
     FROM mensagens m 
     JOIN usuarios u ON u.id = m.id_remetente 
     WHERE (m.id_remetente = :id_remetente AND m.id_destinatario = :id_destinatario)
     OR (m.id_remetente = :id_destinatario AND m.id_destinatario = :id_remetente)
     ORDER BY m.data_envio ASC"
);
$stmt->bindParam(':id_remetente', $usuarioId);
$stmt->bindParam(':id_destinatario', $alunoId);
$stmt->execute();

$mensagens = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Chat com <?= htmlspecialchars($alunoNome) ?></title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<h3>Conversando com <?= htmlspecialchars($alunoNome) ?></h3>

<!-- Exibe mensagens -->
<div id="mensagens">
  <?php foreach($mensagens as $m): ?>
    <div class="message">
      <strong><?= htmlspecialchars($m['nome']) ?>:</strong> <?= nl2br(htmlspecialchars($m['mensagem'])) ?> <i><?= $m['data_envio'] ?></i>
    </div>
  <?php endforeach; ?>
</div>

<div class="input-box">
  <input type="text" id="mensagem" placeholder="Digite sua mensagem..." autocomplete="off">
  <button onclick="enviarMensagem()">Enviar</button>
</div>

<button onclick="fecharChat()">Fechar Chat</button>

<script>
function enviarMensagem() {
  const mensagem = document.getElementById('mensagem').value.trim();
  if (!mensagem) return;

  fetch('enviar_mensagem.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
      destinatario_id: <?= $alunoId ?>,
      mensagem: mensagem
    })
  }).then(response => {
    if (response.ok) {
      document.getElementById('mensagem').value = ''; // Limpa o campo de mensagem
      location.reload(); // Recarrega as mensagens
    }
  });
}

function fecharChat() {
  window.location.href = 'iniciar_chat.php'; // Redireciona para a página inicial de chat
}
</script>

</body>
</html>
