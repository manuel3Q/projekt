<?php

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/php_veci/funkcie.php';
require_once __DIR__ . '/php_veci/auth.php';

requireGuest();

$conn = getConnection();
$error = '';
$oldEmail = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = $_POST['email']    ?? '';
    $password = $_POST['password'] ?? '';
    $oldEmail = trim($email);

    $result = loginCheck($conn, $email, $password);

    if ($result['ok']) {
        loginUser($result['user']);
        $conn->close();
        setFlash('success', 'Vitaj späť, ' . $result['user']['name'] . '!');

        $redirect = $_SESSION['redirect_after_login'] ?? '/projekt/index.php';
        unset($_SESSION['redirect_after_login']);
        redirect($redirect);
    } else {
        $error = $result['error'];
    }
}

$pageTitle = 'Prihlásenie';
require_once __DIR__ . '/php_veci/header.php';
?>

<div class="row justify-content-center mt-3">
    <div class="col-sm-10 col-md-7 col-lg-5">

        <div class="text-center mb-4">
            <div class="auth-logo">?</div>
            <h1 class="auth-title">Prihlásenie</h1>
            <p class="auth-sub">Pokračuj v Q&amp;A Board</p>
        </div>

        <div class="form-card">

            <?php if ($error): ?>
                <div class="alert-auth-error">
                    <i class="bi bi-exclamation-circle me-2"></i><?= clean($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="/projekt/login.php" novalidate>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <div class="input-icon-wrap">
                        <i class="bi bi-envelope input-icon"></i>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-control input-with-icon"
                            value="<?= clean($oldEmail) ?>"
                            placeholder="jan@example.com"
                            autofocus
                        >
                    </div>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label">Heslo</label>
                    <div class="input-icon-wrap">
                        <i class="bi bi-lock input-icon"></i>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control input-with-icon"
                            placeholder="Tvoje heslo"
                        >
                    </div>
                </div>

                <button type="submit" class="btn-primary-custom w-100 justify-content-center" style="padding: 0.7rem;">
                    <i class="bi bi-box-arrow-in-right"></i>Prihlásiť sa
                </button>

            </form>

            <div class="auth-divider"><span>alebo</span></div>

            <p class="text-center" style="color: var(--clr-muted); font-size: 0.9rem; margin: 0;">
                Nemáš účet?
                <a href="/projekt/register.php" class="auth-link">Zaregistrovať sa</a>
            </p>

        </div>
    </div>
</div>

<?php
$conn->close();
require_once __DIR__ . '/php_veci/footer.php';
?>