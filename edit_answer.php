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

$stmt = $conn->prepare('SELECT * FROM answers WHERE id = ? AND question_id = ?');
$stmt->bind_param('ii', $id, $questionId);
$stmt->execute();
$result = $stmt->get_result();
$answer = $result->fetch_assoc();
$stmt->close();

if (!$answer) {
    setFlash('error', 'Odpoveď nebola nájdená.');
    redirect('/projekt/question.php?id=' . $questionId);
}

$errors = [];
$old = $answer;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body   = trim($_POST['body']   ?? '');
    $author = trim($_POST['author'] ?? '');

    $old = array_merge($answer, compact('body', 'author'));

    if (mb_strlen($body) < 10) {
        $errors['body'] = 'Odpoveď musí mať aspoň 10 znakov.';
    }
    if (mb_strlen($author) < 2) {
        $errors['author'] = 'Zadaj meno (aspoň 2 znaky).';
    } elseif (mb_strlen($author) > 100) {
        $errors['author'] = 'Meno je príliš dlhé.';
    }

    if (empty($errors)) {
        $stmt = $conn->prepare(
            'UPDATE answers SET body = ?, author = ? WHERE id = ? AND question_id = ?'
        );
        $stmt->bind_param('ssii', $body, $author, $id, $questionId);

        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            setFlash('success', 'Odpoveď bola upravená.');
            redirect('/projekt/question.php?id=' . $questionId . '#answer-' . $id);
        } else {
            $errors['db'] = 'Chyba databázy: ' . $conn->error;
            $stmt->close();
        }
    }
}

$pageTitle = 'Upraviť odpoveď';
require_once __DIR__ . '/php_veci/header.php';
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/projekt/index.php">Otázky</a></li>
        <li class="breadcrumb-item"><a href="/projekt/question.php?id=<?= $questionId ?>">Otázka #<?= $questionId ?></a></li>
        <li class="breadcrumb-item active">Upraviť odpoveď</li>
    </ol>
</nav>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="form-card">
            <h1 style="font-size:1.8rem;">Upraviť odpoveď</h1>

            <?php if (!empty($errors['db'])): ?>
                <div class="alert alert-danger"><?= clean($errors['db']) ?></div>
            <?php endif; ?>

            <form method="POST"
                  action="/projekt/edit_answer.php?id=<?= $id ?>&question_id=<?= $questionId ?>"
                  novalidate>

                <div class="mb-4">
                    <label for="body" class="form-label">Text odpovede <span class="text-warning">*</span></label>
                    <textarea
                        id="body"
                        name="body"
                        class="form-control <?= isset($errors['body']) ? 'is-invalid' : '' ?>"
                        rows="6"
                    ><?= clean($old['body']) ?></textarea>
                    <?php if (isset($errors['body'])): ?>
                        <div class="invalid-feedback"><?= clean($errors['body']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-4">
                    <label for="author" class="form-label">Meno autora <span class="text-warning">*</span></label>
                    <input
                        type="text"
                        id="author"
                        name="author"
                        class="form-control <?= isset($errors['author']) ? 'is-invalid' : '' ?>"
                        value="<?= clean($old['author']) ?>"
                        maxlength="100"
                        style="max-width: 300px;"
                    >
                    <?php if (isset($errors['author'])): ?>
                        <div class="invalid-feedback"><?= clean($errors['author']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="d-flex gap-3">
                    <button type="submit" class="btn-primary-custom">
                        <i class="bi bi-floppy"></i>Uložiť zmeny
                    </button>
                    <a href="/projekt/question.php?id=<?= $questionId ?>" class="btn-secondary-custom">
                        <i class="bi bi-x-lg"></i>Zrušiť
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$conn->close();
require_once __DIR__ . '/php_veci/footer.php';
?>