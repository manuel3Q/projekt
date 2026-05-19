<?php

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/php_veci/funkcie.php';
require_once __DIR__ . '/php_veci/auth.php';

requireLogin();

$conn = getConnection();
$categories = getCategories();
$errors = [];
$old = ['title' => '', 'body' => '', 'author' => '', 'category' => 'Všeobecné'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title    = trim($_POST['title']    ?? '');
    $body     = trim($_POST['body']     ?? '');
    $author   = trim($_POST['author']   ?? '');
    $category = trim($_POST['category'] ?? '');

    $old = compact('title', 'body', 'author', 'category');

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
            'INSERT INTO questions (title, body, author, category) VALUES (?, ?, ?, ?)'
        );
        $stmt->bind_param('ssss', $title, $body, $author, $category);

        if ($stmt->execute()) {
            $newId = $conn->insert_id;
            $stmt->close();
            $conn->close();
            setFlash('success', 'Otázka bola úspešne pridaná!');
            redirect('/projekt/question.php?id=' . $newId);
        } else {
            $errors['db'] = 'Chyba databázy: ' . $conn->error;
            $stmt->close();
        }
    }
}

$pageTitle = 'Nová otázka';
require_once __DIR__ . '/php_veci/header.php';
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/projekt/index.php">Otázky</a></li>
        <li class="breadcrumb-item active">Nová otázka</li>
    </ol>
</nav>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="form-card">
            <h1 style="font-size:1.8rem;">Položiť otázku</h1>

            <?php if (!empty($errors['db'])): ?>
                <div class="alert alert-danger"><?= clean($errors['db']) ?></div>
            <?php endif; ?>

            <form method="POST" action="/projekt/ask.php" novalidate>

                <div class="mb-4">
                    <label for="title" class="form-label">
                        Nadpis otázky <span class="text-warning">*</span>
                    </label>
                    <input
                        type="text"
                        id="title"
                        name="title"
                        class="form-control <?= isset($errors['title']) ? 'is-invalid' : '' ?>"
                        value="<?= clean($old['title']) ?>"
                        placeholder="Napr. Ako funguje rekurzia v PHP?"
                        maxlength="255"
                    >
                    <?php if (isset($errors['title'])): ?>
                        <div class="invalid-feedback"><?= clean($errors['title']) ?></div>
                    <?php else: ?>
                        <div class="form-text">Buď konkrétny a výstižný (min. 10 znakov).</div>
                    <?php endif; ?>
                </div>

                <div class="mb-4">
                    <label for="body" class="form-label">
                        Popis otázky <span class="text-warning">*</span>
                    </label>
                    <textarea
                        id="body"
                        name="body"
                        class="form-control <?= isset($errors['body']) ? 'is-invalid' : '' ?>"
                        rows="7"
                        placeholder="Opíš problém čo najpresnejšie. Čo si skúšal? Aké chyby dostávaš?"
                    ><?= clean($old['body']) ?></textarea>
                    <?php if (isset($errors['body'])): ?>
                        <div class="invalid-feedback"><?= clean($errors['body']) ?></div>
                    <?php else: ?>
                        <div class="form-text">Min. 20 znakov. Čím presnejšie, tým lepšia odpoveď.</div>
                    <?php endif; ?>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-sm-6">
                        <label for="author" class="form-label">
                            Tvoje meno <span class="text-warning">*</span>
                        </label>
                        <input
                            type="text"
                            id="author"
                            name="author"
                            class="form-control <?= isset($errors['author']) ? 'is-invalid' : '' ?>"
                            value="<?= clean($old['author']) ?>"
                            placeholder="Napr. Ján Novák"
                            maxlength="100"
                        >
                        <?php if (isset($errors['author'])): ?>
                            <div class="invalid-feedback"><?= clean($errors['author']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="col-sm-6">
                        <label for="category" class="form-label">
                            Kategória <span class="text-warning">*</span>
                        </label>
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
                        <i class="bi bi-send"></i>Odoslať otázku
                    </button>
                    <a href="/projekt/index.php" class="btn-secondary-custom">
                        <i class="bi bi-arrow-left"></i>Zrušiť
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