<?php
declare(strict_types=1);
require __DIR__ . '/includes/app.php';
require_login();

if (($_SESSION['user']['role'] ?? '') !== 'instructor' || !is_int($_SESSION['user']['instructor_id'] ?? null)) {
    http_response_code(403);
    exit('Este perfil no está asociado a un instructor.');
}

$statement = db()->prepare(
    'SELECT first_name, last_name, email, specialty
     FROM instructors
     WHERE id = :instructor_id'
);
$statement->execute(['instructor_id' => $_SESSION['user']['instructor_id']]);
$instructor = $statement->fetch();
if (!$instructor) {
    http_response_code(404);
    exit('No se encontró el perfil asociado a esta cuenta.');
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mi perfil | SENA</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="app-page">
    <aside class="sidebar">
        <a class="brand" href="mi_perfil.php">
            <span class="brand-mark">S</span>
            <span><strong>SENA</strong><small>Portal del instructor</small></span>
        </a>
        <div class="sidebar-label">MI ESPACIO</div>
        <nav class="side-nav" aria-label="Navegación principal">
            <a class="nav-link active" href="#profile"><span class="nav-icon">◎</span> Mi perfil</a>
        </nav>
        <div class="sidebar-footer">
            <div class="security-indicator"><span></span> Sesión protegida</div>
            <p>Solo puedes consultar la información asociada a tu perfil.</p>
        </div>
    </aside>
    <main class="main-content" id="profile">
        <header class="topbar">
            <div class="breadcrumb">Portal del instructor <span>/</span> Mi perfil</div>
            <div class="account">
                <span class="avatar"><?= e(mb_strtoupper(mb_substr($instructor['first_name'], 0, 1))) ?></span>
                <span class="account-name"><?= e($_SESSION['user']['name']) ?></span>
                <form action="logout.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <button class="button-quiet" type="submit">Salir</button>
                </form>
            </div>
        </header>
        <div class="page-wrap">
            <section class="page-heading">
                <div>
                    <p class="eyebrow">PORTAL DEL INSTRUCTOR</p>
                    <h1>Hola, <?= e($instructor['first_name']) ?></h1>
                    <p class="muted">Consulta tu información de perfil.</p>
                </div>
            </section>
            <section class="panel">
                <div class="panel-heading"><div><p class="eyebrow">INFORMACIÓN PERSONAL</p><h2>Mi perfil</h2></div></div>
                <div class="profile-details">
                    <div><span>Nombre</span><strong><?= e($instructor['first_name'] . ' ' . $instructor['last_name']) ?></strong></div>
                    <div><span>Correo institucional</span><strong><?= e($instructor['email']) ?></strong></div>
                    <div><span>Área / especialidad</span><strong><?= e($instructor['specialty']) ?></strong></div>
                </div>
            </section>
            <footer class="page-footer">Datos personales de acceso restringido · SENA Talento Humano</footer>
        </div>
    </main>
</body>
</html>
