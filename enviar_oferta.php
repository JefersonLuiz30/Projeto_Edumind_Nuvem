<?php
session_start();
include('db.php');

// Verificar se a requisição é via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obter dados da requisição
    $data = json_decode(file_get_contents('php://input'), true);
    $destinatarioId = $data['destinatarioId'];
    $offer = $data['offer'];  // A oferta do peerConnection

    // Aqui você pode armazenar a oferta no banco de dados ou enviar para o destinatário
    // Exemplo de inserção no banco (modifique conforme necessário)
    $stmt = $conn->prepare("INSERT INTO video_ofertas (destinatario_id, offer) VALUES (:destinatarioId, :offer)");
    $stmt->bindParam(':destinatarioId', $destinatarioId);
    $stmt->bindParam(':offer', json_encode($offer));  // Converter a oferta em JSON antes de salvar
    $stmt->execute();

    // Retornar resposta
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
}
?>
