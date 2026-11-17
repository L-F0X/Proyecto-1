<?php
declare(strict_types=1);
require __DIR__ . '/includes/app.php';

if (isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$flash = take_flash();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    $statement = db()->prepare('SELECT id, full_name, email, password_hash, role, instructor_id FROM users WHERE email = :email LIMIT 1');
    $statement->execute(['email' => $email]);
    $user = $statement->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        unset($_SESSION['csrf_token']);
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'name' => $user['full_name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'instructor_id' => $user['instructor_id'] === null ? null : (int) $user['instructor_id'],
        ];
        header('Location: ' . ($user['role'] === 'instructor' ? 'mi_perfil.php' : 'index.php'));
        exit;
    }

    $error = 'Correo o contraseña incorrectos.';
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión | SENA Talento Humano</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">
    <main class="auth-card">
        <a class="brand brand-auth" href="login.php" aria-label="SENA Talento Humano">
            <span class="brand-mark">S</span>
            <span><strong>SENA</strong><small>Talento humano</small></span>
        </a>
        <p class="eyebrow">Portal administrativo</p>
        <h1>Bienvenido</h1>
        <p class="muted">Ingresa con tu cuenta autorizada para continuar.</p>
        <?php if ($flash): ?>
            <div class="alert <?= $flash['type'] === 'error' ? 'alert-error' : 'alert-success' ?>" role="status"><?= e($flash['message']) ?></div>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <div class="alert alert-error" role="alert"><?= e($error) ?></div>
        <?php endif; ?>
        <form method="post" class="form-stack">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <label for="email">Correo institucional</label>
            <input id="email" name="email" type="email" autocomplete="username" required maxlength="190" value="<?= e($_POST['email'] ?? '') ?>">
            <label for="password">Contraseña</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required>
            <button class="button button-primary button-full" type="submit">Iniciar sesión <span aria-hidden="true">→</span></button>
        </form>
        <p class="auth-footnote">¿Primera configuración? <a href="setup.php">Crear la cuenta administradora inicial</a></p>
    </main>
    <p class="auth-privacy">Acceso restringido. Los datos se deben tratar conforme a las políticas de protección de datos personales.</p>
</body>
</html>
