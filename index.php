<?php

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/php_veci/funkcie.php';
require_once __DIR__ . '/php_veci/auth.php';

requireLogin();

$conn = getConnection();

$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$categories = getCategories();

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

if ($category && in_array($category, $categories)) {
    $stmt = $conn->prepare(
        'SELECT * FROM questions WHERE category = ?
         ORDER BY created_at DESC'
    );
    $stmt->bind_param('s', $category);
} elseif ($search !== '') {
    $like = '%' . $search . '%';
    $stmt = $conn->prepare(
        'SELECT * FROM questions WHERE title LIKE ? OR body LIKE ?
         ORDER BY created_at DESC'
    );
    $stmt->bind_param('ss', $like, $like);
} else {
    $stmt = $conn->prepare('SELECT * FROM questions ORDER BY created_at DESC');
}

$stmt->execute();
$result = $stmt->get_result();
$questions = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$totalQ = $conn->query('SELECT COUNT(*) FROM questions')->fetch_row()[0];
$totalA = $conn->query('SELECT COUNT(*) FROM answers')->fetch_row()[0];
$totalAcc = $conn->query('SELECT COUNT(DISTINCT question_id) FROM answers WHERE is_accepted=1')->fetch_row()[0];

$pageTitle = 'Všetky otázky';
require_once __DIR__ . '/php_veci/header.php';
?>

<div class="page-hero">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h1>Otázky &amp; <span>Odpovede</span></h1>
            <p>Školský Q&amp;A Ssosta's board – pýtaj sa, odpovedaj, uč sa.</p>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <a href="/projekt/ask.php" class="btn-ask" style="font-size:1rem; padding:0.65rem 1.4rem;">
                <i class="bi bi-plus-lg me-1"></i>Nová otázka
            </a>
        </div>
    </div>
</div>

<div class="stats-row">
    <div class="stat-box">
        <div class="stat-number"><?= $totalQ ?></div>
        <div class="stat-label"><i class="bi bi-question-circle me-1"></i>otázok</div>
    </div>
    <div class="stat-box">
        <div class="stat-number"><?= $totalA ?></div>
        <div class="stat-label"><i class="bi bi-chat-text me-1"></i>odpovedí</div>
    </div>
    <div class="stat-box">
        <div class="stat-number"><?= $totalAcc ?></div>
        <div class="stat-label"><i class="bi bi-check-circle me-1"></i>vyriešených</div>
    </div>
</div>

<form method="GET" action="/projekt/index.php" class="mb-3">
    <div class="input-group">
        <input
            type="text"
            name="search"
            class="form-control"
            placeholder="Hľadať otázky…"
            value="<?= clean($search) ?>"
        >
        <button class="btn-primary-custom" type="submit" style="border-radius: 0 8px 8px 0; padding: 0.6rem 1.2rem;">
            <i class="bi bi-search"></i>
        </button>
    </div>
</form>

<div class="filter-bar">
    <a href="/projekt/index.php" class="filter-chip <?= $category === '' && $search === '' ? 'active' : '' ?>">
        Všetky
    </a>
    <?php foreach ($categories as $cat): ?>
        <a href="/projekt/index.php?category=<?= urlencode($cat) ?>"
           class="filter-chip <?= $category === $cat ? 'active' : '' ?>">
            <?= clean($cat) ?>
        </a>
    <?php endforeach; ?>
</div>

<?php if (empty($questions)): ?>
    <div class="empty-state">
        <div class="empty-icon"><i class="bi bi-search"></i></div>
        <h3>Žiadne otázky</h3>
        <p>
            <?php if ($search): ?>
                Nenašli sa žiadne otázky pre „<?= clean($search) ?>".
            <?php elseif ($category): ?>
                V kategórii „<?= clean($category) ?>" zatiaľ nie sú otázky.
            <?php else: ?>
                Buď prvý, kto sa spýta!
            <?php endif; ?>
        </p>
        <a href="/projekt/ask.php" class="btn-primary-custom mt-2">
            <i class="bi bi-plus-lg"></i>Položiť otázku
        </a>
    </div>
<?php else: ?>
    <?php foreach ($questions as $q): ?>
        <?php
            $ansCount = getAnswerCount($conn, $q['id']);
            $hasAccepted = hasAcceptedAnswer($conn, $q['id']);
        ?>
        <a href="/projekt/question.php?id=<?= $q['id'] ?>"
           class="question-card <?= $hasAccepted ? 'answered' : '' ?>">
            <div class="question-card-title"><?= clean($q['title']) ?></div>
            <div class="question-card-body"><?= clean(truncate($q['body'])) ?></div>
            <div class="question-meta">
                <span class="badge-cat"><?= clean($q['category']) ?></span>
                <?php if ($hasAccepted): ?>
                    <span class="badge-answered"><i class="bi bi-check-circle-fill me-1"></i>Vyriešené</span>
                <?php endif; ?>
                <span class="answer-count">
                    <i class="bi bi-chat-text"></i>
                    <?= $ansCount ?> <?= $ansCount === 1 ? 'odpoveď' : ($ansCount < 5 ? 'odpovede' : 'odpovedí') ?>
                </span>
                <span><i class="bi bi-person me-1"></i><?= clean($q['author']) ?></span>
                <span><i class="bi bi-clock me-1"></i><?= formatDate($q['created_at']) ?></span>
            </div>
        </a>
    <?php endforeach; ?>
<?php endif; ?>

<?php
$conn->close();
require_once __DIR__ . '/php_veci/footer.php';
?>