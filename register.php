<?php

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/php_veci/funkcie.php';
require_once __DIR__ . '/php_veci/auth.php';

requireGuest();

$conn = getConnection();
$errors = [];
$old = ['name' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name            = $_POST['name']             ?? '';
    $email           = $_POST['email']            ?? '';
    $password        = $_POST['password']         ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    $old = ['name' => trim($name), 'email' => trim($email)];

    $result = registerUser($conn, $name, $email, $password, $passwordConfirm);

    if ($result['ok']) {
        loginUser($result['user']);
        $conn->close();
        setFlash('success', 'Účet bol vytvorený. Vitaj, ' . $result['user']['name'] . '!');
        redirect('/projekt/index.php');
    } else {
        $errors = $result['errors'];
    }
}

$pageTitle = 'Registrácia';
require_once __DIR__ . '/php_veci/header.php';
?>

<div class="row justify-content-center mt-3">
    <div class="col-sm-10 col-md-7 col-lg-5">

        <div class="text-center mb-4">
            <div class="auth-logo">?</div>
            <h1 class="auth-title">Vytvor účet</h1>
            <p class="auth-sub">Pripoj sa ku Q&amp;A Board komunite</p>
        </div>

        <div class="form-card">

            <?php if (!empty($errors['db'])): ?>
                <div class="alert alert-danger"><?= clean($errors['db']) ?></div>
            <?php endif; ?>

            <form method="POST" action="/projekt/register.php" novalidate>

                <div class="mb-3">
                    <label for="name" class="form-label">Celé meno <span class="text-warning">*</span></label>
                    <div class="input-icon-wrap">
                        <i class="bi bi-person input-icon"></i>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            class="form-control input-with-icon <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                            value="<?= clean($old['name']) ?>"
                            placeholder="Ján Novák"
                            maxlength="100"
                            autofocus
                        >
                    </div>
                    <?php if (isset($errors['name'])): ?>
                        <div class="field-error"><?= clean($errors['name']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email <span class="text-warning">*</span></label>
                    <div class="input-icon-wrap">
                        <i class="bi bi-envelope input-icon"></i>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-control input-with-icon <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                            value="<?= clean($old['email']) ?>"
                            placeholder="jan@example.com"
                            maxlength="150"
                        >
                    </div>
                    <?php if (isset($errors['email'])): ?>
                        <div class="field-error"><?= clean($errors['email']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Heslo <span class="text-warning">*</span></label>
                    <div class="input-icon-wrap">
                        <i class="bi bi-lock input-icon"></i>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control input-with-icon <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                            placeholder="Min. 8 znakov"
                        >
                    </div>
                    <?php if (isset($errors['password'])): ?>
                        <div class="field-error"><?= clean($errors['password']) ?></div>
                    <?php else: ?>
                        <div class="form-text">Aspoň 8 znakov.</div>
                    <?php endif; ?>
                </div>

                <div class="mb-4">
                    <label for="password_confirm" class="form-label">Potvrdiť heslo <span class="text-warning">*</span></label>
                    <div class="input-icon-wrap">
                        <i class="bi bi-lock-fill input-icon"></i>
                        <input
                            type="password"
                            id="password_confirm"
                            name="password_confirm"
                            class="form-control input-with-icon <?= isset($errors['password_confirm']) ? 'is-invalid' : '' ?>"
                            placeholder="Zopakuj heslo"
                        >
                    </div>
                    <?php if (isset($errors['password_confirm'])): ?>
                        <div class="field-error"><?= clean($errors['password_confirm']) ?></div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn-primary-custom w-100 justify-content-center" style="padding: 0.7rem;">
                    <i class="bi bi-person-plus"></i>Zaregistrovať sa
                </button>

            </form>

            <div class="auth-divider"><span>alebo</span></div>

            <p class="text-center" style="color: var(--clr-muted); font-size: 0.9rem; margin: 0;">
                Už máš účet?
                <a href="/projekt/login.php" class="auth-link">Prihlásiť sa</a>
            </p>

        </div>
    </div>
</div>

<?php
$conn->close();
require_once __DIR__ . '/php_veci/footer.php';
?>