<?php

if (session_status() === PHP_SESSION_NONE) session_start();

function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

function currentUserId(): ?int {
    return $_SESSION['user_id'] ?? null;
}

function currentUserName(): ?string {
    return $_SESSION['user_name'] ?? null;
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        setFlash('error', 'Pre túto akciu sa musíš prihlásiť.');
        redirect('/projekt/login.php');
    }
}

function requireGuest(): void {
    if (isLoggedIn()) {
        redirect('/projekt/index.php');
    }
}

function loginUser(array $user): void {
    session_regenerate_id(true);
    $_SESSION['user_id']    = $user['id'];
    $_SESSION['user_name']  = $user['name'];
    $_SESSION['user_email'] = $user['email'];
}

function logoutUser(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']
        );
    }
    session_destroy();
}

function registerUser(mysqli $conn, string $name, string $email, string $password, string $passwordConfirm): array {
    $errors = [];
 
    $name = trim($name);
    if (mb_strlen($name) < 2) {
        $errors['name'] = 'Meno musí mať aspoň 2 znaky.';
    } elseif (mb_strlen($name) > 100) {
        $errors['name'] = 'Meno je príliš dlhé (max 100 znakov).';
    }
 
    $email = trim(strtolower($email));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Zadaj platný email.';
    } elseif (mb_strlen($email) > 150) {
        $errors['email'] = 'Email je príliš dlhý.';
    } else {
        $stmt = $conn->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors['email'] = 'Tento email je už zaregistrovaný.';
        }
        $stmt->close();
    }
 
    if (mb_strlen($password) < 8) {
        $errors['password'] = 'Heslo musí mať aspoň 8 znakov.';
    } elseif ($password !== $passwordConfirm) {
        $errors['password_confirm'] = 'Heslá sa nezhodujú.';
    }
 
    if (!empty($errors)) {
        return ['ok' => false, 'errors' => $errors];
    }
 
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $conn->prepare('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)');
    $stmt->bind_param('sss', $name, $email, $hash);
 
    if (!$stmt->execute()) {
        $stmt->close();
        return ['ok' => false, 'errors' => ['db' => 'Chyba databázy: ' . $conn->error]];
    }
 
    $userId = $conn->insert_id;
    $stmt->close();
 
    return ['ok' => true, 'user' => ['id' => $userId, 'name' => $name, 'email' => $email]];
}
function loginCheck(mysqli $conn, string $email, string $password): array {
    $email = trim(strtolower($email));
 
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'Neplatný email.'];
    }
 
    $stmt = $conn->prepare('SELECT id, name, email, password_hash FROM users WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
 
    if (!$user) {
        return ['ok' => false, 'error' => 'Nesprávny email alebo heslo.'];
    }
 
    if (!password_verify($password, $user['password_hash'])) {
        return ['ok' => false, 'error' => 'Nesprávny email alebo heslo.'];
    }
 
    return ['ok' => true, 'user' => $user];
}