<?php
$managerPageTitle = $managerPageTitle ?? 'Principal';
$activeManagerPage = $activeManagerPage ?? 'principal';
$managerNavigation = [
    'principal' => ['index.php', '⌂', 'Principal'],
    'administrativos' => ['administrativos.php', '▦', 'Administrativos'],
    'instructores' => ['instructores.php', '◎', 'Instructores'],
];
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($managerPageTitle) ?> | SENA Talento Humano</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/app.js" defer></script>
</head>
<body class="app-page">
    <aside class="sidebar">
        <a class="brand" href="index.php">
            <span class="brand-mark">S</span>
            <span><strong>SENA</strong><small>Talento humano</small></span>
        </a>
        <div class="sidebar-label">GESTIÓN</div>
        <nav class="side-nav" aria-label="Navegación principal">
            <?php foreach ($managerNavigation as $key => [$href, $icon, $label]): ?>
                <a class="nav-link <?= $activeManagerPage === $key ? 'active' : '' ?>" href="<?= e($href) ?>" <?= $activeManagerPage === $key ? 'aria-current="page"' : '' ?>>
                    <span class="nav-icon"><?= e($icon) ?></span><?= e($label) ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-footer">
            <div class="security-indicator"><span></span> Sesión protegida</div>
            <p>Información de uso exclusivo para personal autorizado.</p>
        </div>
    </aside>
    <main class="main-content">
        <header class="topbar">
            <div class="breadcrumb">Administración <span>/</span> <?= e($managerPageTitle) ?></div>
            <div class="account">
                <span class="avatar"><?= e(mb_strtoupper(mb_substr((string) $_SESSION['user']['name'], 0, 1))) ?></span>
                <span class="account-name"><?= e($_SESSION['user']['name']) ?></span>
                <form action="logout.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <button class="button-quiet" type="submit">Salir</button>
                </form>
            </div>
        </header>
        <div class="page-wrap">
