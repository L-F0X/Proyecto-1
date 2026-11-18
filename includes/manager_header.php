<?php
$managerPageTitle = $managerPageTitle ?? 'Principal';
$activeManagerPage = $activeManagerPage ?? 'principal';
$isInstructor = ($_SESSION['user']['role'] ?? '') === 'instructor';
$navigationSections = $isInstructor
    ? [
        ['title' => 'MI ESPACIO', 'links' => [
            'mi_perfil' => ['mi_perfil.php', '◎', 'Mi perfil'],
        ]],
        ['title' => 'GESTIÓN', 'links' => [
            'administrativos' => ['administrativos.php', '▦', 'Administrativos'],
            'instructores' => ['instructores.php', '◎', 'Instructores'],
        ]],
        ['title' => 'CUENTA', 'links' => [
            'cuenta' => ['cuenta.php', '◉', 'Mi cuenta'],
        ]],
    ]
    : [
        ['title' => 'PRINCIPAL', 'links' => [
            'principal' => ['index.php', '⌂', 'Principal'],
        ]],
        ['title' => 'GESTIÓN', 'links' => [
            'administrativos' => ['administrativos.php', '▦', 'Administrativos'],
            'instructores' => ['instructores.php', '◎', 'Instructores'],
        ]],
        ['title' => 'CUENTA', 'links' => array_merge(
            ['cuenta' => ['cuenta.php', '◉', 'Mi cuenta']],
            ($_SESSION['user']['role'] ?? '') === 'admin'
                ? ['usuarios' => ['usuarios.php', '♙', 'Usuarios']]
                : []
        )],
    ];
$sharedLocation = directory_location();
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
        <div class="sidebar-topbar">
            <a class="brand" href="<?= $isInstructor ? 'mi_perfil.php' : 'index.php' ?>">
                <span class="brand-mark">S</span>
                <span><strong>SENA</strong><small><?= $isInstructor ? 'Portal del analista' : 'Talento humano' ?></small></span>
            </a>
            <button class="menu-toggle" type="button" aria-label="Abrir menú de navegación" aria-controls="sidebar-navigation" aria-expanded="false" data-sidebar-toggle>
                <span></span><span></span><span></span>
            </button>
        </div>
        <div class="sidebar-navigation" id="sidebar-navigation">
            <?php foreach ($navigationSections as $section): ?>
                <div class="sidebar-label"><?= e($section['title']) ?></div>
                <nav class="side-nav" aria-label="<?= e($section['title']) ?>">
                    <?php foreach ($section['links'] as $key => [$href, $icon, $label]): ?>
                        <a class="nav-link <?= $activeManagerPage === $key ? 'active' : '' ?>" href="<?= e($href) ?>" <?= $activeManagerPage === $key ? 'aria-current="page"' : '' ?>>
                            <span class="nav-icon"><?= e($icon) ?></span><?= e($label) ?>
                        </a>
                    <?php endforeach; ?>
                </nav>
            <?php endforeach; ?>
        </div>
        <div class="sidebar-footer">
            <div class="security-indicator"><span></span> Sesión protegida</div>
            <p class="location-sidebar"><strong>Sede</strong> <?= e($sharedLocation['site_name'] ?: '—') ?></p>
            <p class="location-sidebar"><strong>Regional</strong> <?= e($sharedLocation['regional'] ?: '—') ?></p>
        </div>
    </aside>
    <main class="main-content">
        <header class="topbar">
            <div class="breadcrumb"><?= $isInstructor ? 'Portal del analista' : 'Administración' ?> <span>/</span> <?= e($managerPageTitle) ?></div>
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
