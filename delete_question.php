<?php

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/php_veci/funkcie.php';
require_once __DIR__ . '/php_veci/auth.php';

requireLogin();

if (session_status() === PHP_SESSION_NONE) session_start();

$conn = getConnection();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) redirect('/projekt/index.php');

$stmt = $conn->prepare('SELECT id, title FROM questions WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$question = $result->fetch_assoc();
$stmt->close();

if (!$question) {
    setFlash('error', 'Otázka nebola nájdená.');
    redirect('/projekt/index.php');
}

$stmt = $conn->prepare('DELETE FROM questions WHERE id = ?');
$stmt->bind_param('i', $id);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    setFlash('success', 'Otázka „' . $question['title'] . '" bola vymazaná.');
    redirect('/projekt/index.php');
} else {
    setFlash('error', 'Chyba pri vymazávaní: ' . $conn->error);
    $stmt->close();
    $conn->close();
    redirect('/projekt/question.php?id=' . $id);
}