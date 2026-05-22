<?php

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/php_veci/funkcie.php';
require_once __DIR__ . '/php_veci/auth.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$conn = getConnection();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    redirect('/projekt/index.php');
}

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

$answerErrors = [];
$answerOld = ['body' => '', 'author' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_answer') {
    $ansBody   = trim($_POST['body']   ?? '');
    $ansAuthor = trim($_POST['author'] ?? '');

    $answerOld = ['body' => $ansBody, 'author' => $ansAuthor];

    if (mb_strlen($ansBody) < 10) {
        $answerErrors['body'] = 'Odpoveď musí mať aspoň 10 znakov.';
    }
    if (mb_strlen($ansAuthor) < 2) {
        $answerErrors['author'] = 'Zadaj meno (aspoň 2 znaky).';
    } elseif (mb_strlen($ansAuthor) > 100) {
        $answerErrors['author'] = 'Meno je príliš dlhé (max 100 znakov).';
    }

    if (empty($answerErrors)) {
        $stmt = $conn->prepare(
            'INSERT INTO answers (question_id, body, author) VALUES (?, ?, ?)'
        );
        $stmt->bind_param('iss', $id, $ansBody, $ansAuthor);

        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            setFlash('success', 'Odpoveď bola pridaná!');
            redirect('/projekt/question.php?id=' . $id);
        } else {
            $answerErrors['db'] = 'Chyba databázy: ' . $conn->error;
            $stmt->close();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'accept_answer') {
    $answerId = (int) ($_POST['answer_id'] ?? 0);
    if ($answerId > 0) {
        $conn->query("UPDATE answers SET is_accepted = 0 WHERE question_id = $id");
        $stmt = $conn->prepare('UPDATE answers SET is_accepted = 1 WHERE id = ? AND question_id = ?');
        $stmt->bind_param('ii', $answerId, $id);
        $stmt->execute();
        $stmt->close();
    }
    $conn->close();
    setFlash('success', 'Odpoveď bola označená ako akceptovaná.');
    redirect('/projekt/question.php?id=' . $id);
}

$stmt = $conn->prepare(
    'SELECT * FROM answers WHERE question_id = ? ORDER BY is_accepted DESC, created_at ASC'
);
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$answers = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$pageTitle = $question['title'];
require_once __DIR__ . '/php_veci/header.php';
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/projekt/index.php">Otázky</a></li>
        <li class="breadcrumb-item active"><?= clean(truncate($question['title'], 60)) ?></li>
    </ol>
</nav>

<div class="question-detail">
    <div class="d-flex gap-2 align-items-center mb-3 flex-wrap">
        <span class="badge-cat"><?= clean($question['category']) ?></span>
        <?php if (hasAcceptedAnswer($conn, $id)): ?>
            <span class="badge-answered"><i class="bi bi-check-circle-fill me-1"></i>Vyriešené</span>
        <?php endif; ?>
    </div>

    <h1><?= clean($question['title']) ?></h1>

    <div class="question-body mb-4"><?= clean($question['body']) ?></div>

    <div class="d-flex gap-3 flex-wrap" style="font-size:0.85rem; color:var(--clr-muted);">
        <span><i class="bi bi-person me-1"></i><?= clean($question['author']) ?></span>
        <span><i class="bi bi-clock me-1"></i><?= formatDate($question['created_at']) ?></span>
        <?php if ($question['updated_at'] !== $question['created_at']): ?>
            <span><i class="bi bi-pencil me-1"></i>upravené <?= formatDate($question['updated_at']) ?></span>
        <?php endif; ?>
    </div>

    <div class="d-flex gap-2 mt-3">
        <a href="/projekt/edit_question.php?id=<?= $id ?>" class="btn-secondary-custom">
            <i class="bi bi-pencil"></i>Upraviť
        </a>
        <a href="/projekt/delete_question.php?id=<?= $id ?>"
           class="btn-danger-custom"
           onclick="return confirm('Naozaj chceš vymazať túto otázku aj s odpoveďami?')">
            <i class="bi bi-trash"></i>Vymazať
        </a>
    </div>
</div>

<div class="answers-section">
    <h2>
        <i class="bi bi-chat-text me-2"></i>
        <?= count($answers) ?> <?= count($answers) === 1 ? 'Odpoveď' : (count($answers) < 5 ? 'Odpovede' : 'Odpovedí') ?>
    </h2>

    <?php if (empty($answers)): ?>
        <div class="empty-state" style="padding: 2rem 1rem;">
            <div class="empty-icon"><i class="bi bi-chat-square-text"></i></div>
            <h3>Zatiaľ žiadne odpovede</h3>
            <p>Buď prvý, kto odpovie!</p>
        </div>
    <?php else: ?>
        <?php foreach ($answers as $ans): ?>
            <div class="answer-card <?= $ans['is_accepted'] ? 'accepted' : '' ?>" id="answer-<?= $ans['id'] ?>">
                <?php if ($ans['is_accepted']): ?>
                    <span class="accepted-badge"><i class="bi bi-check-lg me-1"></i>Akceptovaná</span>
                <?php endif; ?>

                <div class="answer-body"><?= clean($ans['body']) ?></div>

                <div style="font-size:0.82rem; color:var(--clr-muted);">
                    <i class="bi bi-person me-1"></i><?= clean($ans['author']) ?>
                    &nbsp;·&nbsp;
                    <i class="bi bi-clock me-1"></i><?= formatDate($ans['created_at']) ?>
                </div>

                <div class="answer-actions">
                    <?php if (!$ans['is_accepted']): ?>
                        <form method="POST" action="/projekt/question.php?id=<?= $id ?>" style="display:inline;">
                            <input type="hidden" name="action" value="accept_answer">
                            <input type="hidden" name="answer_id" value="<?= $ans['id'] ?>">
                            <button type="submit" class="btn-accept-custom">
                                <i class="bi bi-check-lg"></i>Akceptovať
                            </button>
                        </form>
                    <?php endif; ?>

                    <a href="/projekt/edit_answer.php?id=<?= $ans['id'] ?>&question_id=<?= $id ?>"
                       class="btn-secondary-custom">
                        <i class="bi bi-pencil"></i>Upraviť
                    </a>

                    <a href="/projekt/delete_answer.php?id=<?= $ans['id'] ?>&question_id=<?= $id ?>"
                       class="btn-danger-custom"
                       onclick="return confirm('Naozaj chceš vymazať túto odpoveď?')">
                        <i class="bi bi-trash"></i>Vymazať
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div class="form-card mt-4">
    <h2 style="font-size:1.3rem; margin-bottom:1.25rem;">
        <i class="bi bi-reply me-2"></i>Pridať odpoveď
    </h2>

    <?php if (!empty($answerErrors['db'])): ?>
        <div class="alert alert-danger"><?= clean($answerErrors['db']) ?></div>
    <?php endif; ?>

    <form method="POST" action="/projekt/question.php?id=<?= $id ?>" novalidate>
        <input type="hidden" name="action" value="add_answer">

        <div class="mb-3">
            <label for="ans_body" class="form-label">
                Tvoja odpoveď <span class="text-warning">*</span>
            </label>
            <textarea
                id="ans_body"
                name="body"
                class="form-control <?= isset($answerErrors['body']) ? 'is-invalid' : '' ?>"
                rows="5"
                placeholder="Napíš svoju odpoveď…"
            ><?= clean($answerOld['body']) ?></textarea>
            <?php if (isset($answerErrors['body'])): ?>
                <div class="invalid-feedback"><?= clean($answerErrors['body']) ?></div>
            <?php endif; ?>
        </div>

        <div class="mb-4">
            <label for="ans_author" class="form-label">
                Tvoje meno <span class="text-warning">*</span>
            </label>
            <input
                type="text"
                id="ans_author"
                name="author"
                class="form-control <?= isset($answerErrors['author']) ? 'is-invalid' : '' ?>"
                value="<?= clean($answerOld['author']) ?>"
                placeholder="Napr. Ján Novák"
                maxlength="100"
                style="max-width: 300px;"
            >
            <?php if (isset($answerErrors['author'])): ?>
                <div class="invalid-feedback"><?= clean($answerErrors['author']) ?></div>
            <?php endif; ?>
        </div>

        <button type="submit" class="btn-primary-custom">
            <i class="bi bi-send"></i>Odoslať odpoveď
        </button>
    </form>
</div>

<?php
$conn->close();
require_once __DIR__ . '/php_veci/footer.php';
?>