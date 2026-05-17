<?php

require_once __DIR__ . '/php_veci/funkcie.php';
require_once __DIR__ . '/php_veci/auth.php';
 
$name = currentUserName() ?? 'Užívateľ';
logoutUser();
 
if (session_status() === PHP_SESSION_NONE) session_start();
setFlash('success', $name . ', bol si úspešne odhlásený.');
redirect('/projekt/login.php');
 