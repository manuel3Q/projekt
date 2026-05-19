<?php
function clean(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

function setFlash(string $type, string $message): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function getCategories(): array {
    return ['Všeobecné', 'PHP', 'Databázy', 'HTML/CSS', 'JavaScript', 'Iné'];
}

function truncate(string $text, int $length = 120): string {
    if (mb_strlen($text) <= $length) return $text;
    return mb_substr($text, 0, $length) . '…';
}

function formatDate(string $datetime): string {
    $ts = strtotime($datetime);
    return date('d.m.Y o H:i', $ts);
}

function getAnswerCount(mysqli $conn, int $questionId): int {
    $stmt = $conn->prepare('SELECT COUNT(*) FROM answers WHERE question_id = ?');
    $stmt->bind_param('i', $questionId);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    return (int) $count;
}

function hasAcceptedAnswer(mysqli $conn, int $questionId): bool {
    $stmt = $conn->prepare('SELECT id FROM answers WHERE question_id = ? AND is_accepted = 1 LIMIT 1');
    $stmt->bind_param('i', $questionId);
    $stmt->execute();
    $result = $stmt->get_result();
    $has = $result->num_rows > 0;
    $stmt->close();
    return $has;
}