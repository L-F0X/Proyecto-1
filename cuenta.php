<?php
declare(strict_types=1);
require __DIR__ . '/includes/app.php';
require_login();

$statement = db()->prepare(
    'SELECT u.full_name, u.email, u.role, u.created_at,
            i.first_name AS instructor_first_name, i.last_name AS instructor_last_name
     FROM users u
     LEFT JOIN instructors i ON i.id = u.instructor_id
     WHERE u.id = :user_id'
);
$statement->execute(['user_id' => $_SESSION['user']['id']]);
$account = $statement->fetch();
if (!$account) {
    http_response_code(404);
    exit('No se encontró la cuenta activa.');
}

$roleLabels = [
    'admin' => 'Administrador',
    'talento_humano' => 'Talento Humano',
    'instructor' => 'Analista',
];
$accountRole = $roleLabels[$account['role']] ?? $account['role'];
$accountCreated = date('d/m/Y', strtotime($account['created_at']));
$linkedInstructor = trim(($account['instructor_first_name'] ?? '') . ' ' . ($account['instructor_last_name'] ?? ''));
$managerPageTitle = 'Mi cuenta';
$activeManagerPage = 'cuenta';
require __DIR__ . '/includes/manager_header.php';
?>
            <section class="page-heading">
                <div>
                    <p class="eyebrow">CUENTA</p>
                    <h1>Mi cuenta</h1>
                    <p class="muted">Consulta la información de acceso asociada a tu perfil.</p>
                </div>
            </section>
            <section class="panel">
                <div class="panel-heading">
                    <div><p class="eyebrow">PERFIL DE USUARIO</p><h2>Información de la cuenta</h2></div>
                </div>
                <div class="profile-details">
                    <div><span>Nombre</span><strong><?= e($account['full_name']) ?></strong></div>
                    <div><span>Correo</span><strong><?= e($account['email']) ?></strong></div>
                    <div><span>Perfil de acceso</span><strong><?= e($accountRole) ?></strong></div>
                    <div><span>Instructor vinculado</span><strong><?= e($linkedInstructor !== '' ? $linkedInstructor : '—') ?></strong></div>
                    <div><span>Cuenta creada</span><strong><?= e($accountCreated) ?></strong></div>
                </div>
            </section>
<?php require __DIR__ . '/includes/manager_footer.php'; ?>
