<?php

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/php_veci/funkcie.php';
require_once __DIR__ . '/php_veci/auth.php';

requireLogin();

if (session_status() === PHP_SESSION_NONE) session_start();

$conn = getConnection();
$categories = getCategories();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) redirect('/projekt/index.php');

$stmt = $conn->prepare('SELECT * FROM questions WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$question = $result->fetch_assoc();
$stmt->close();

if (!$question) {
    setFlash('error', 'Otázka nebola nájdená.');
    redirect('/projekt/index.php');
}

$errors = [];
$old = $question;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title    = trim($_POST['title']    ?? '');
    $body     = trim($_POST['body']     ?? '');
    $author   = trim($_POST['author']   ?? '');
    $category = trim($_POST['category'] ?? '');

    $old = array_merge($question, compact('title', 'body', 'author', 'category'));

    if (mb_strlen($title) < 10) {
        $errors['title'] = 'Nadpis musí mať aspoň 10 znakov.';
    } elseif (mb_strlen($title) > 255) {
        $errors['title'] = 'Nadpis je príliš dlhý (max 255 znakov).';
    }

    if (mb_strlen($body) < 20) {
        $errors['body'] = 'Popis otázky musí mať aspoň 20 znakov.';
    }

    if (mb_strlen($author) < 2) {
        $errors['author'] = 'Zadaj meno (aspoň 2 znaky).';
    } elseif (mb_strlen($author) > 100) {
        $errors['author'] = 'Meno je príliš dlhé (max 100 znakov).';
    }

    if (!in_array($category, $categories)) {
        $errors['category'] = 'Vyber platnú kategóriu.';
    }

    if (empty($errors)) {
        $stmt = $conn->prepare(
            'UPDATE questions SET title = ?, body = ?, author = ?, category = ? WHERE id = ?'
        );
        $stmt->bind_param('ssssi', $title, $body, $author, $category, $id);

        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            setFlash('success', 'Otázka bola úspešne upravená.');
            redirect('/projekt/question.php?id=' . $id);
        } else {
            $errors['db'] = 'Chyba databázy: ' . $conn->error;
            $stmt->close();
        }
    }
}

$pageTitle = 'Upraviť otázku';
require_once __DIR__ . '/php_veci/header.php';
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/projekt/index.php">Otázky</a></li>
        <li class="breadcrumb-item"><a href="/projekt/question.php?id=<?= $id ?>">Otázka #<?= $id ?></a></li>
        <li class="breadcrumb-item active">Upraviť</li>
    </ol>
</nav>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="form-card">
            <h1 style="font-size:1.8rem;">Upraviť otázku</h1>

            <?php if (!empty($errors['db'])): ?>
                <div class="alert alert-danger"><?= clean($errors['db']) ?></div>
            <?php endif; ?>

            <form method="POST" action="/projekt/edit_question.php?id=<?= $id ?>" novalidate>

                <div class="mb-4">
                    <label for="title" class="form-label">Nadpis otázky <span class="text-warning">*</span></label>
                    <input
                        type="text"
                        id="title"
                        name="title"
                        class="form-control <?= isset($errors['title']) ? 'is-invalid' : '' ?>"
                        value="<?= clean($old['title']) ?>"
                        maxlength="255"
                    >
                    <?php if (isset($errors['title'])): ?>
                        <div class="invalid-feedback"><?= clean($errors['title']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-4">
                    <label for="body" class="form-label">Popis otázky <span class="text-warning">*</span></label>
                    <textarea
                        id="body"
                        name="body"
                        class="form-control <?= isset($errors['body']) ? 'is-invalid' : '' ?>"
                        rows="7"
                    ><?= clean($old['body']) ?></textarea>
                    <?php if (isset($errors['body'])): ?>
                        <div class="invalid-feedback"><?= clean($errors['body']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-sm-6">
                        <label for="author" class="form-label">Meno autora <span class="text-warning">*</span></label>
                        <input
                            type="text"
                            id="author"
                            name="author"
                            class="form-control <?= isset($errors['author']) ? 'is-invalid' : '' ?>"
                            value="<?= clean($old['author']) ?>"
                            maxlength="100"
                        >
                        <?php if (isset($errors['author'])): ?>
                            <div class="invalid-feedback"><?= clean($errors['author']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="col-sm-6">
                        <label for="category" class="form-label">Kategória <span class="text-warning">*</span></label>
                        <select
                            id="category"
                            name="category"
                            class="form-select <?= isset($errors['category']) ? 'is-invalid' : '' ?>"
                        >
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= clean($cat) ?>"
                                    <?= $old['category'] === $cat ? 'selected' : '' ?>>
                                    <?= clean($cat) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['category'])): ?>
                            <div class="invalid-feedback"><?= clean($errors['category']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="d-flex gap-3 align-items-center">
                    <button type="submit" class="btn-primary-custom">
                        <i class="bi bi-floppy"></i>Uložiť zmeny
                    </button>
                    <a href="/projekt/question.php?id=<?= $id ?>" class="btn-secondary-custom">
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