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
$managerPageTitle = 'Mi perfil';
$activeManagerPage = 'mi_perfil';
require __DIR__ . '/includes/manager_header.php';
?>
            <section class="page-heading" id="profile">
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
<?php require __DIR__ . '/includes/manager_footer.php'; ?>
