<?php
declare(strict_types=1);
require __DIR__ . '/includes/app.php';
require_manager();

$instructors = db()->query(
    'SELECT id, first_name, last_name, email, document_number, phone, personal_email,
            affiliation_type, job_title, specialty, role_name, site_name, site_address,
            site_id, regional, extension_ip, contract_end_date, contract_end_date_raw
     FROM instructors
     ORDER BY last_name, first_name'
)->fetchAll();
$flash = take_flash();
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
                            <input type="search" placeholder="Buscar por nombre, documento o vinculación..." aria-label="Buscar instructores por nombre, documento de identidad o tipo de vinculación" data-table-filter="instructors-table" data-search-columns="1,2,3,6">
                        </label>
                        <button class="button button-secondary" type="button" data-export-table="instructors-table" data-export-filename="instructores">↓ Exportar CSV</button>
                    </div>
                </div>
                <div class="directory-date-filter" role="group" aria-label="Filtrar por fecha de terminación">
                    <span>Fecha de terminación</span>
                    <label><span>Desde</span><input type="date" aria-label="Fecha de terminación desde" data-table-filter="instructors-table" data-date-bound="from"></label>
                    <label><span>Hasta</span><input type="date" aria-label="Fecha de terminación hasta" data-table-filter="instructors-table" data-date-bound="to"></label>
                </div>
                <div class="table-wrap">
                    <table id="instructors-table">
                        <thead>
                            <tr>
                                <th scope="col">Correo electrónico institucional (@sena.edu.co)</th>
                                <th scope="col">Nombres</th>
                                <th scope="col">Apellidos</th>
                                <th scope="col">Documento de identidad</th>
                                <th scope="col">Número celular</th>
                                <th scope="col">Correo personal</th>
                                <th scope="col">Tipo de vinculación</th>
                                <th scope="col">Cargo</th>
                                <th scope="col">Rol</th>
                                <th scope="col">Nombre sede</th>
                                <th scope="col">Dirección sede</th>
                                <th scope="col">ID sede</th>
                                <th scope="col">Regional</th>
                                <th scope="col">Número EXT (IP)</th>
                                <th scope="col" class="date-column">Fecha de terminación de contrato (dd/mm/yy)</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!$instructors): ?>
                            <tr><td class="empty-state" colspan="15">Aún no hay instructores importados.</td></tr>
                        <?php else: ?>
                            <?php foreach ($instructors as $instructor): ?>
                                <?php
                                $contractEnd = $instructor['contract_end_date']
                                    ? date('d/m/y', strtotime($instructor['contract_end_date']))
                                    : ($instructor['contract_end_date_raw'] ?: '—');
                                ?>
                                <tr>
                                    <td><?= e($instructor['email'] ?: '—') ?></td>
                                    <td><?= e($instructor['first_name']) ?></td>
                                    <td><?= e($instructor['last_name']) ?></td>
                                    <td><?= e($instructor['document_number'] ?: '—') ?></td>
                                    <td><?= e($instructor['phone'] ?: '—') ?></td>
                                    <td><?= e($instructor['personal_email'] ?: '—') ?></td>
                                    <td><?= e($instructor['affiliation_type'] ?: '—') ?></td>
                                    <td><?= e($instructor['job_title'] ?: $instructor['specialty'] ?: '—') ?></td>
                                    <td><?= e($instructor['role_name'] ?: '—') ?></td>
                                    <td><?= e($instructor['site_name'] ?: '—') ?></td>
                                    <td><?= e($instructor['site_address'] ?: '—') ?></td>
                                    <td><?= e($instructor['site_id'] ?: '—') ?></td>
                                    <td><?= e($instructor['regional'] ?: '—') ?></td>
                                    <td><?= e($instructor['extension_ip'] ?: '—') ?></td>
                                    <td class="date-column" data-filter-date="<?= e($instructor['contract_end_date'] ?? '') ?>"><?= e($contractEnd) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
<?php require __DIR__ . '/includes/manager_footer.php'; ?>
