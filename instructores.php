<?php
declare(strict_types=1);
require __DIR__ . '/includes/app.php';
require_directory_viewer();

$instructors = db()->query(
    'SELECT id, first_name, last_name, email, document_number, phone, personal_email,
            affiliation_type, job_title, specialty, area,
            contract_start_date, contract_end_date, contract_end_date_raw
     FROM instructors
     ORDER BY last_name, first_name'
)->fetchAll();
$flash = take_flash();
$canManageDirectory = ($_SESSION['user']['role'] ?? '') === 'admin';
$affiliationOptions = array_values(array_unique(array_merge(
    ['Contratista', 'Planta'],
    db()->query("SELECT DISTINCT TRIM(affiliation_type) FROM instructors WHERE affiliation_type IS NOT NULL AND TRIM(affiliation_type) <> ''")
        ->fetchAll(PDO::FETCH_COLUMN)
)));
$managerPageTitle = 'Instructores';
$activeManagerPage = 'instructores';
require __DIR__ . '/includes/manager_header.php';
?>
            <section class="page-heading">
                <div>
                    <p class="eyebrow">GESTIÓN DEL TALENTO HUMANO</p>
                    <h1>Instructores</h1>
                    <p class="muted">Consulta el directorio del personal instructor.</p>
                </div>
                <?php if ($canManageDirectory): ?>
                    <button class="button button-primary" type="button" data-open-dialog="create-staff-dialog">+ Añadir instructor</button>
                <?php endif; ?>
            </section>

            <?php if ($flash): ?>
                <div class="alert <?= $flash['type'] === 'error' ? 'alert-error' : 'alert-success' ?>" role="status"><?= e($flash['message']) ?></div>
            <?php endif; ?>

            <section class="panel">
                <div class="panel-heading">
                    <div>
                        <p class="eyebrow">PERSONAL INSTRUCTOR</p>
                        <h2>Instructores <span class="count-pill"><?= e(count($instructors)) ?></span></h2>
                    </div>
                    <div class="directory-toolbar">
                        <label class="search-box">
                            <span aria-hidden="true">⌕</span>
                            <input type="search" placeholder="Buscar por nombre, documento o vinculación..." aria-label="Buscar instructores por nombre, documento de identidad o tipo de vinculación" data-table-filter="instructors-table" data-search-columns="1,2,0,6">
                        </label>
                        <button class="button button-secondary" type="button" data-export-table="instructors-table" data-export-filename="instructores">↓ Exportar CSV</button>
                    </div>
                </div>
                <div class="table-wrap">
                    <table id="instructors-table" class="directory-table">
                        <thead>
                            <tr>
                                <th scope="col">Documento de identidad</th>
                                <th scope="col">Nombres</th>
                                <th scope="col">Apellidos</th>
                                <th scope="col">Correo electrónico institucional</th>
                                <th scope="col">Número celular</th>
                                <th scope="col">Correo personal</th>
                                <th scope="col">Tipo de vinculación</th>
                                <th scope="col" class="cargo-column">Cargo</th>
                                <th scope="col" class="role-column">Area</th>
                                <th scope="col">Supervisor o jefe inmediato</th>
                                <th scope="col" class="date-column">Vigencia del contrato</th>
                                <?php if ($canManageDirectory): ?><th scope="col" class="no-export staff-actions-column">Acciones</th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!$instructors): ?>
                            <tr><td class="empty-state" colspan="<?= $canManageDirectory ? 12 : 11 ?>">Aún no hay instructores importados.</td></tr>
                        <?php else: ?>
                            <?php foreach ($instructors as $instructor): ?>
                                <?php
                                $contractStart = $instructor['contract_start_date']
                                    ? date('d/m/Y', strtotime($instructor['contract_start_date']))
                                    : '—';
                                $contractEnd = $instructor['contract_end_date']
                                    ? date('d/m/Y', strtotime($instructor['contract_end_date']))
                                    : ($instructor['contract_end_date_raw'] ?: '—');
                                ?>
                                <tr>
                                    <td><?= e($instructor['document_number'] ?: '—') ?></td>
                                    <td><?= e($instructor['first_name']) ?></td>
                                    <td><?= e($instructor['last_name']) ?></td>
                                    <td><?= e($instructor['email'] ?: '—') ?></td>
                                    <td><?= e($instructor['phone'] ?: '—') ?></td>
                                    <td><?= e($instructor['personal_email'] ?: '—') ?></td>
                                    <td><?= e($instructor['affiliation_type'] ?: '—') ?></td>
                                    <td class="cargo-column"><?= e($instructor['job_title'] ?: '—') ?></td>
                                    <td class="role-column"><?= e($instructor['area'] ?: '—') ?></td>
                                    <td>--</td>
                                    <td class="date-column"><span class="contract-date-range"><span><small>Desde</small><strong><?= e($contractStart) ?></strong></span><span><small>Hasta</small><strong><?= e($contractEnd) ?></strong></span></span></td>
                                    <?php if ($canManageDirectory): ?>
                                        <td class="no-export staff-actions-column">
                                            <div class="user-actions">
                                                <button class="button-small" type="button" data-edit-staff
                                                    data-staff-id="<?= e($instructor['id']) ?>"
                                                    data-document-number="<?= e($instructor['document_number'] ?? '') ?>"
                                                    data-first-name="<?= e($instructor['first_name']) ?>"
                                                    data-last-name="<?= e($instructor['last_name']) ?>"
                                                    data-email="<?= e($instructor['email'] ?? '') ?>"
                                                    data-phone="<?= e($instructor['phone'] ?? '') ?>"
                                                    data-personal-email="<?= e($instructor['personal_email'] ?? '') ?>"
                                                    data-affiliation-type="<?= e($instructor['affiliation_type'] ?? '') ?>"
                                                    data-job-title="<?= e($instructor['job_title'] ?? '') ?>"
                                                    data-area="<?= e($instructor['area'] ?? '') ?>"
                                                    data-contract-start-date="<?= e($instructor['contract_start_date'] ?? '') ?>"
                                                    data-contract-end-date="<?= e($instructor['contract_end_date'] ?? '') ?>">Editar</button>
                                                <form action="staff_actions.php" method="post" data-confirm-staff-delete>
                                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="category" value="instructor">
                                                    <input type="hidden" name="staff_id" value="<?= e($instructor['id']) ?>">
                                                    <button class="button-small button-danger" type="submit">Eliminar</button>
                                                </form>
                                            </div>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
            <?php if ($canManageDirectory): ?>
                <?php
                $staffCategory = 'instructor';
                $staffFormTitle = 'instructor';
                require __DIR__ . '/includes/staff_forms.php';
                ?>
            <?php endif; ?>
<?php require __DIR__ . '/includes/manager_footer.php'; ?>
