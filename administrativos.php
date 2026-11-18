<?php
declare(strict_types=1);
require __DIR__ . '/includes/app.php';
require_directory_viewer();

$administrativeStaff = db()->query(
    'SELECT id, first_name, last_name, email, document_number, phone, personal_email,
    affiliation_type, job_title, specialty, dependency, supervisor, contract_number,
    contract_start_date, contract_end_date, contract_end_date_raw
     FROM administrative_staff
     ORDER BY last_name, first_name'
)->fetchAll();
$flash = take_flash();
$canManageDirectory = ($_SESSION['user']['role'] ?? '') === 'admin';
$affiliationOptions = array_values(array_unique(array_merge(
    ['Contratista', 'Planta'],
    db()->query("SELECT DISTINCT TRIM(affiliation_type) FROM administrative_staff WHERE affiliation_type IS NOT NULL AND TRIM(affiliation_type) <> ''")
        ->fetchAll(PDO::FETCH_COLUMN)
)));
$managerPageTitle = 'Administrativos';
$activeManagerPage = 'administrativos';
require __DIR__ . '/includes/manager_header.php';
?>
            <section class="page-heading">
                <div>
                    <p class="eyebrow">GESTIÓN DEL TALENTO HUMANO</p>
                    <h1>Personal administrativo</h1>
                    <p class="muted">Consulta el directorio del personal administrativo.</p>
                </div>
                <?php if ($canManageDirectory): ?>
                    <button class="button button-primary" type="button" data-open-dialog="create-staff-dialog">+ Añadir administrativo</button>
                <?php endif; ?>
            </section>

            <?php if ($flash): ?>
                <div class="alert <?= $flash['type'] === 'error' ? 'alert-error' : 'alert-success' ?>" role="status"><?= e($flash['message']) ?></div>
            <?php endif; ?>

            <section class="panel">
                <div class="panel-heading">
                    <div>
                        <p class="eyebrow">PERSONAL ADMINISTRATIVO</p>
                        <h2>Administrativos <span class="count-pill"><?= e(count($administrativeStaff)) ?></span></h2>
                    </div>
                    <div class="directory-toolbar">
                        <label class="search-box">
                            <span aria-hidden="true">⌕</span>
                            <input type="search" placeholder="Buscar por nombre, contrato o documento..." aria-label="Buscar administrativos por nombre, número de contrato o documento de identidad" data-table-filter="administrative-table" data-search-columns="1,2,10,0">
                        </label>
                        <button class="button button-secondary" type="button" data-export-table="administrative-table" data-export-filename="personal-administrativo">↓ Exportar CSV</button>
                    </div>
                </div>
                <div class="table-wrap">
                    <table id="administrative-table" class="directory-table">
                        <thead>
                            <tr>
                                <th scope="col">Documento de identidad</th>
                                <th scope="col">Nombres</th>
                                <th scope="col">Apellidos</th>
                                <th scope="col">Correo electrónico institucional</th>
                                <th scope="col">Número celular</th>
                                <th scope="col">Correo personal</th>
                                <th scope="col">Tipo de vinculación</th>
                                <th scope="col">Cargo</th>
                                <th scope="col">Dependencia</th>
                                <th scope="col">Supervisor o Jefe Inmediato</th>
                                <th scope="col">Número de contrato</th>
                                <th scope="col" class="date-column">Vigencia del contrato</th>
                                <?php if ($canManageDirectory): ?><th scope="col" class="no-export staff-actions-column">Acciones</th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!$administrativeStaff): ?>
                            <tr><td class="empty-state" colspan="<?= $canManageDirectory ? 13 : 12 ?>">Aún no hay personal administrativo importado.</td></tr>
                        <?php else: ?>
                            <?php foreach ($administrativeStaff as $person): ?>
                                <?php
                                $contractStart = $person['contract_start_date']
                                    ? date('d/m/Y', strtotime($person['contract_start_date']))
                                    : '—';
                                $contractEnd = $person['contract_end_date']
                                    ? date('d/m/Y', strtotime($person['contract_end_date']))
                                    : ($person['contract_end_date_raw'] ?: '—');
                                ?>
                                <tr>
                                    <td><?= e($person['document_number'] ?: '—') ?></td>
                                    <td><?= e($person['first_name']) ?></td>
                                    <td><?= e($person['last_name']) ?></td>
                                    <td><?= e($person['email'] ?: '—') ?></td>
                                    <td><?= e($person['phone'] ?: '—') ?></td>
                                    <td><?= e($person['personal_email'] ?: '—') ?></td>
                                    <td><?= e($person['affiliation_type'] ?: '—') ?></td>
                                    <td><?= e($person['job_title'] ?: $person['specialty'] ?: '—') ?></td>
                                    <td><?= e($person['dependency'] ?: '—') ?></td>
                                    <td><?= e($person['supervisor'] ?: '—') ?></td>
                                    <td><?= e($person['contract_number'] ?: '—') ?></td>
                                    <td class="date-column"><span class="contract-date-range"><span><small>Desde</small><strong><?= e($contractStart) ?></strong></span><span><small>Hasta</small><strong><?= e($contractEnd) ?></strong></span></span></td>
                                    <?php if ($canManageDirectory): ?>
                                        <td class="no-export staff-actions-column">
                                            <div class="user-actions">
                                                <button class="button-small" type="button" data-edit-staff
                                                    data-staff-id="<?= e($person['id']) ?>"
                                                    data-document-number="<?= e($person['document_number'] ?? '') ?>"
                                                    data-first-name="<?= e($person['first_name']) ?>"
                                                    data-last-name="<?= e($person['last_name']) ?>"
                                                    data-email="<?= e($person['email'] ?? '') ?>"
                                                    data-phone="<?= e($person['phone'] ?? '') ?>"
                                                    data-personal-email="<?= e($person['personal_email'] ?? '') ?>"
                                                    data-affiliation-type="<?= e($person['affiliation_type'] ?? '') ?>"
                                                    data-job-title="<?= e($person['job_title'] ?: $person['specialty'] ?: '') ?>"
                                                    data-dependency="<?= e($person['dependency'] ?? '') ?>"
                                                    data-supervisor="<?= e($person['supervisor'] ?? '') ?>"
                                                    data-contract-number="<?= e($person['contract_number'] ?? '') ?>"
                                                    data-contract-start-date="<?= e($person['contract_start_date'] ?? '') ?>"
                                                    data-contract-end-date="<?= e($person['contract_end_date'] ?? '') ?>">Editar</button>
                                                <form action="staff_actions.php" method="post" data-confirm-staff-delete>
                                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="category" value="administrative">
                                                    <input type="hidden" name="staff_id" value="<?= e($person['id']) ?>">
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
                $staffCategory = 'administrative';
                $staffFormTitle = 'administrativo';
                require __DIR__ . '/includes/staff_forms.php';
                ?>
            <?php endif; ?>
<?php require __DIR__ . '/includes/manager_footer.php'; ?>
