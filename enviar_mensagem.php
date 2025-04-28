<?php
ob_clean();
header('Content-Type: application/json');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db.php';
session_start();

try {
    // Recebe os dados do JavaScript (JSON)
    $data = json_decode(file_get_contents('php://input'), true);

    // Verifica se o usuário está logado
    if (!isset($_SESSION['usuario_id'])) {
        echo json_encode(['success' => false, 'error' => 'Usuário não autenticado.']);
        exit;
    }

    $id_remetente = $_SESSION['usuario_id']; // ALTERADO
    $id_destinatario = $data['id_destinatario'] ?? null;
    $mensagem = $data['mensagem'] ?? '';

    if (!$id_destinatario || !$mensagem) {
        echo json_encode(['success' => false, 'error' => 'Destinatário ou mensagem vazios.']);
        exit;
    }

    // Salva no banco (com os nomes corretos)
    $stmt = $conn->prepare("INSERT INTO mensagens (id_remetente, id_destinatario, mensagem, enviado_em, data_envio) VALUES (?, ?, ?, NOW(), NOW())");
    $ok = $stmt->execute([$id_remetente, $id_destinatario, $mensagem]);

    if ($ok) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erro ao salvar no banco.']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
