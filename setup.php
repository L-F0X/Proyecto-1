<?php
declare(strict_types=1);
require __DIR__ . '/includes/app.php';

$setupKey = app_env('APP_SETUP_KEY');
$error = '';
$complete = false;

$count = (int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn();
if ($count > 0) {
    $complete = true;
}

if (!$complete && $_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $providedKey = (string) ($_POST['setup_key'] ?? '');
    $email = trim((string) ($_POST['email'] ?? ''));
    $name = trim((string) ($_POST['full_name'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (!is_string($setupKey) || $setupKey === '' || !hash_equals($setupKey, $providedKey)) {
        $error = 'La clave de configuración no es válida.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
        $error = 'Ingresa un correo válido.';
    } elseif ($name === '' || mb_strlen($name) > 150) {
        $error = 'Ingresa el nombre del administrador.';
    } elseif (strlen($password) < 12) {
        $error = 'La contraseña debe tener al menos 12 caracteres.';
    } else {
        try {
            $insert = db()->prepare(
                'INSERT INTO users (full_name, email, password_hash, role) VALUES (:name, :email, :password_hash, :role)'
            );
            $insert->execute([
                'name' => $name,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'role' => 'admin',
            ]);
            set_flash('Cuenta administradora creada. Ya puedes iniciar sesión.');
            header('Location: login.php');
            exit;
        } catch (PDOException $exception) {
            error_log('No fue posible crear el administrador inicial: ' . $exception->getMessage());
            $error = 'No se pudo crear la cuenta. Verifica que el correo no esté registrado e inténtalo de nuevo.';
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Configuración inicial | SENA Talento Humano</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">
    <main class="auth-card">
        <a class="brand brand-auth" href="login.php">
            <span class="brand-mark">S</span>
            <span><strong>SENA</strong><small>Talento humano</small></span>
        </a>
        <p class="eyebrow">Configuración segura</p>
        <h1>Cuenta inicial</h1>
        <?php if ($complete): ?>
            <div class="alert alert-success">La cuenta inicial ya fue creada. La configuración está cerrada.</div>
            <a class="button button-primary button-full" href="login.php">Ir al inicio de sesión</a>
        <?php else: ?>
            <p class="muted">Esta opción solo funciona antes de crear el primer usuario y requiere la clave definida en <code>APP_SETUP_KEY</code>.</p>
            <?php if ($error !== ''): ?>
                <div class="alert alert-error" role="alert"><?= e($error) ?></div>
            <?php endif; ?>
            <form method="post" class="form-stack">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <label for="setup_key">Clave de configuración</label>
                <input id="setup_key" name="setup_key" type="password" required autocomplete="off">
                <label for="full_name">Nombre completo</label>
                <input id="full_name" name="full_name" required maxlength="150" value="<?= e($_POST['full_name'] ?? '') ?>">
                <label for="email">Correo institucional</label>
                <input id="email" name="email" type="email" required maxlength="190" value="<?= e($_POST['email'] ?? '') ?>">
                <label for="password">Contraseña (mínimo 12 caracteres)</label>
                <input id="password" name="password" type="password" required minlength="12" autocomplete="new-password">
                <button class="button button-primary button-full" type="submit">Crear administrador <span aria-hidden="true">→</span></button>
            </form>
        <?php endif; ?>
        <p class="auth-footnote"><a href="login.php">Volver al inicio de sesión</a></p>
    </main>
</body>
</html>
