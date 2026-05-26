<?php

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/php_veci/funkcie.php';
require_once __DIR__ . '/php_veci/auth.php';

requireLogin();

if (session_status() === PHP_SESSION_NONE) session_start();

$conn = getConnection();

$id         = isset($_GET['id'])          ? (int) $_GET['id']          : 0;
$questionId = isset($_GET['question_id']) ? (int) $_GET['question_id'] : 0;

if ($id <= 0 || $questionId <= 0) redirect('/projekt/index.php');

$stmt = $conn->prepare('SELECT id FROM answers WHERE id = ? AND question_id = ?');
$stmt->bind_param('ii', $id, $questionId);
$stmt->execute();
$result = $stmt->get_result();
$answer = $result->fetch_assoc();
$stmt->close();

if (!$answer) {
    setFlash('error', 'Odpoveď nebola nájdená.');
    redirect('/projekt/question.php?id=' . $questionId);
}

$stmt = $conn->prepare('DELETE FROM answers WHERE id = ? AND question_id = ?');
$stmt->bind_param('ii', $id, $questionId);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    setFlash('success', 'Odpoveď bola vymazaná.');
    redirect('/projekt/question.php?id=' . $questionId);
} else {
    setFlash('error', 'Chyba pri vymazávaní: ' . $conn->error);
    $stmt->close();
    $conn->close();
    redirect('/projekt/question.php?id=' . $questionId);
}