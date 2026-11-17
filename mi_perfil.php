<?php
declare(strict_types=1);
require __DIR__ . '/includes/app.php';
require_login();

if (($_SESSION['user']['role'] ?? '') !== 'instructor' || !is_int($_SESSION['user']['instructor_id'] ?? null)) {
    http_response_code(403);
    exit('Este perfil no está asociado a un instructor.');
}

$statement = db()->prepare(
    'SELECT i.first_name, i.last_name, i.email, i.specialty,
            c.contract_number, c.contract_type, c.start_date, c.end_date, c.monthly_value, c.status
     FROM instructors i
     LEFT JOIN contracts c ON c.instructor_id = i.id
     WHERE i.id = :instructor_id
     ORDER BY c.end_date DESC'
);
$statement->execute(['instructor_id' => $_SESSION['user']['instructor_id']]);
$records = $statement->fetchAll();
if (!$records) {
    http_response_code(404);
    exit('No se encontró el perfil asociado a esta cuenta.');
}
$instructor = $records[0];
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
            <a class="nav-link" href="#my-contracts"><span class="nav-icon">▤</span> Mis contratos</a>
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
                    <p class="muted">Consulta tu información de perfil y tus contratos registrados.</p>
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
            <section class="panel" id="my-contracts">
                <div class="panel-heading"><div><p class="eyebrow">INFORMACIÓN LABORAL</p><h2>Mis contratos</h2></div></div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>N.º contrato</th><th>Tipo</th><th>Vigencia</th><th>Valor mensual</th><th>Estado</th></tr></thead>
                        <tbody>
                        <?php
                        $hasContract = false;
                        foreach ($records as $record):
                            if ($record['contract_number'] === null) {
                                continue;
                            }
                            $hasContract = true;
                        ?>
                            <tr>
                                <td><strong class="contract-number"><?= e($record['contract_number']) ?></strong></td>
                                <td><?= e($record['contract_type']) ?></td>
                                <td><?= e(date('d/m/Y', strtotime($record['start_date']))) ?> <span class="date-divider">—</span> <?= e(date('d/m/Y', strtotime($record['end_date']))) ?></td>
                                <td><?= e(number_format((float) $record['monthly_value'], 0, ',', '.')) ?></td>
                                <td><span class="status-badge status-<?= e(strtolower($record['status'])) ?>"><?= e($record['status']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$hasContract): ?><tr><td class="empty-state" colspan="5">No tienes contratos registrados.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
            <footer class="page-footer">Datos personales de acceso restringido · SENA Talento Humano</footer>
        </div>
    </main>
</body>
</html>
