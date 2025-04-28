<?php
session_start();
include('db.php');

if (!isset($_SESSION['usuario_id'])) exit;

/* ---------- FEED DE EVENTOS ---------- */
if (isset($_GET['feed'])) {
    if ($_SESSION['tipo_usuario'] === 'aluno') {
        $stmt = $conn->prepare(
          "SELECT c.id, c.inicio, c.fim, u.nome AS professor_nome
             FROM compromissos c
             JOIN usuarios u ON u.id = c.professor_id
            WHERE c.aluno_id = :uid");
    } else {
        $stmt = $conn->prepare(
          "SELECT c.id, c.inicio, c.fim, u.nome AS aluno_nome
             FROM compromissos c
             JOIN usuarios u ON u.id = c.aluno_id
            WHERE c.professor_id = :uid");
    }

    $stmt->execute([':uid' => $_SESSION['usuario_id']]);
    $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(array_map(function($e){
        return [
            'id'    => $e['id'],
            'title' => isset($e['professor_nome']) 
                       ? 'Consultoria com ' . $e['professor_nome'] 
                       : 'Consulta com ' . $e['aluno_nome'],
            'start' => $e['inicio'],
            'end'   => $e['fim']
        ];
    }, $eventos));
    exit;
}

/* ---------- HORAS LIVRES EM UM DIA ---------- */
if (isset($_GET['horas_livres_dia'])) {
    $dia = $_GET['dia'];
    $todos = [];
    for ($h = 8; $h <= 17; $h++) $todos[] = sprintf('%02d:00', $h);

    $q = $conn->prepare("SELECT DISTINCT DATE_FORMAT(inicio, '%H:00') h
                         FROM compromissos WHERE DATE(inicio) = :d");
    $q->execute([':d' => $dia]);
    $ocup = array_column($q->fetchAll(PDO::FETCH_ASSOC), 'h');

    echo json_encode(array_values(array_diff($todos, $ocup)));
    exit;
}

/* ---------- PROFESSORES DISPONÍVEIS EM UM HORÁRIO ---------- */
if (isset($_GET['prof_livres_slot'])) {
    $dia = $_GET['dia'];
    $hora = $_GET['hora'];
    $ini = "$dia $hora:00";
    $fim = date('Y-m-d H:i:s', strtotime($ini . ' +1 hour'));

    $q = $conn->prepare("SELECT id, nome FROM usuarios
                         WHERE tipo_usuario = 'professor' AND id NOT IN (
                             SELECT professor_id FROM compromissos
                             WHERE inicio < :fim AND fim > :ini
                         )");
    $q->execute([':ini' => $ini, ':fim' => $fim]);
    echo json_encode($q->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

/* ---------- CRIAR COMPROMISSO (ALUNO) ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SESSION['tipo_usuario'] === 'aluno') {
    $d = json_decode(file_get_contents('php://input'), true);

    $professor_id = $d['id_prof'] ?? null;
    $dia = $d['dia'] ?? null;
    $hora = $d['hora'] ?? null;

    if ($professor_id && $dia && $hora) {
        $inicio = "$dia $hora:00";
        $fim = date('Y-m-d H:i:s', strtotime($inicio . ' +1 hour'));

        $stmt = $conn->prepare("INSERT INTO compromissos (professor_id, aluno_id, inicio, fim)
                                VALUES (:p, :a, :i, :f)");
        $stmt->execute([
            ':p' => $professor_id,
            ':a' => $_SESSION['usuario_id'],
            ':i' => $inicio,
            ':f' => $fim
        ]);

        echo json_encode(['ok' => true]);
    } else {
        echo json_encode(['error' => 'Dados incompletos']);
    }
    exit;
}

/* ---------- EXCLUIR COMPROMISSO (PROFESSOR) ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && $_SESSION['tipo_usuario'] === 'professor') {
    $d = json_decode(file_get_contents('php://input'), true);
    $id = (int)($d['id'] ?? 0);

    if ($id) {
        $stmt = $conn->prepare(
            "DELETE FROM compromissos
             WHERE id = :id AND professor_id = :uid"
        );
        $stmt->execute([
            ':id' => $id,
            ':uid' => $_SESSION['usuario_id']
        ]);
    }

    echo json_encode(['ok' => true]);
    exit;
}
?>
