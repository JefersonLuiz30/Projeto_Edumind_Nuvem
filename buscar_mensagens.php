<?php
session_start();
include('db.php');

// Verificar se a sessão está ativa e o destinatário foi passado
if (!isset($_SESSION['usuario_id']) || !isset($_GET['id_destinatario'])) {
    echo json_encode([]);
    exit;
}

$id_remetente = $_SESSION['usuario_id'];
$id_destinatario = $_GET['id_destinatario'];

// Consultar as mensagens
try {
    $stmt = $conn->prepare(
        "SELECT u.nome, m.mensagem, m.data_envio 
         FROM mensagens m 
         JOIN usuarios u ON u.id = m.id_remetente 
         WHERE (m.id_remetente = :id_remetente AND m.id_destinatario = :id_destinatario)
         OR (m.id_remetente = :id_destinatario AND m.id_destinatario = :id_remetente)
         ORDER BY m.data_envio ASC"
    );
    $stmt->bindParam(':id_remetente', $id_remetente);
    $stmt->bindParam(':id_destinatario', $id_destinatario);
    $stmt->execute();

    $mensagens = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($mensagens) {
        echo json_encode($mensagens);
    } else {
        echo json_encode([]);
    }
} catch (Exception $e) {
    echo json_encode(['error' => 'Erro ao buscar mensagens: ' . $e->getMessage()]);
}
?>
