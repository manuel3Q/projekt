<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? clean($pageTitle) . ' – ' : '' ?>Q&amp;A Ssosta's Board</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/projekt/css/style.css">
</head>
<body>

<?php
if (!function_exists('isLoggedIn')) {
    require_once __DIR__ . '/auth.php';
}
?>

<nav class="navbar navbar-expand-lg">
    <div class="kont">
        <a class="navbar-brand" href="/projekt/index.php">
            <span class="brand-icon">?</span>
            <span class="brand-text">Q&amp;A<span class="brand-accent">Ssosta's Board</span></span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-2">
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>"
                       href="/projekt/index.php">
                        <i class="bi bi-list-ul me-1"></i>Otázky
                    </a>
                </li>

                <?php if (isLoggedIn()): ?>
                    <li class="nav-item">
                        <a class="btn btn-ask" href="/projekt/ask.php">
                            <i class="bi bi-plus-lg me-1"></i>Spýtať sa
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <button class="nav-user-btn dropdown-toggle" data-bs-toggle="dropdown">
                            <span class="nav-avatar"><?= mb_strtoupper(mb_substr(currentUserName(), 0, 1)) ?></span>
                            <span class="nav-username"><?= clean(currentUserName()) ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end nav-dropdown">
                            <li>
                                <span class="dropdown-email">
                                    <i class="bi bi-envelope me-2"></i><?= clean($_SESSION['user_email'] ?? '') ?>
                                </span>
                            </li>
                            <li><hr class="dropdown-divider" style="border-color: var(--clr-border);"></li>
                            <li>
                                <a class="dropdown-item nav-dd-item" href="/projekt/logout.php">
                                    <i class="bi bi-box-arrow-right me-2"></i>Odhlásiť sa
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'login.php' ? 'active' : '' ?>"
                           href="/projekt/login.php">
                            <i class="bi bi-box-arrow-in-right me-1"></i>Prihlásiť sa
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-ask" href="/projekt/register.php">
                            <i class="bi bi-person-plus me-1"></i>Registrácia
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<main class="container py-4">

# pridat upozornenia?