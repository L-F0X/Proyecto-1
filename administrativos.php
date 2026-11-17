<?php
declare(strict_types=1);
require __DIR__ . '/includes/app.php';
require_manager();

$administrativeStaff = db()->query(
    'SELECT id, first_name, last_name, email, document_number, phone, personal_email,
            affiliation_type, job_title, specialty, role_name, supervisor, site_name,
            site_address, site_id, regional, contract_number, contract_end_date,
            contract_end_date_raw, vpn_access
     FROM administrative_staff
     ORDER BY last_name, first_name'
)->fetchAll();
$flash = take_flash();
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
                            <input type="search" placeholder="Buscar por nombre, contrato o documento..." aria-label="Buscar administrativos por nombre, número de contrato o documento de identidad" data-table-filter="administrative-table" data-search-columns="1,2,14,3">
                        </label>
                        <button class="button button-secondary" type="button" data-export-table="administrative-table" data-export-filename="personal-administrativo">↓ Exportar CSV</button>
                    </div>
                </div>
                <div class="directory-date-filter" role="group" aria-label="Filtrar por fecha de terminación">
                    <span>Fecha de terminación</span>
                    <label><span>Desde</span><input type="date" aria-label="Fecha de terminación desde" data-table-filter="administrative-table" data-date-bound="from"></label>
                    <label><span>Hasta</span><input type="date" aria-label="Fecha de terminación hasta" data-table-filter="administrative-table" data-date-bound="to"></label>
                </div>
                <div class="table-wrap">
                    <table id="administrative-table">
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
                                <th scope="col">Supervisor o Jefe Inmediato</th>
                                <th scope="col">Nombre sede</th>
                                <th scope="col">Dirección sede</th>
                                <th scope="col">ID sede</th>
                                <th scope="col">Regional</th>
                                <th scope="col">Número de contrato</th>
                                <th scope="col" class="date-column">Fecha de terminación de contrato (dd/mm/yy)</th>
                                <th scope="col">VPN</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!$administrativeStaff): ?>
                            <tr><td class="empty-state" colspan="17">Aún no hay personal administrativo importado.</td></tr>
                        <?php else: ?>
                            <?php foreach ($administrativeStaff as $person): ?>
                                <?php
                                $contractEnd = $person['contract_end_date']
                                    ? date('d/m/y', strtotime($person['contract_end_date']))
                                    : ($person['contract_end_date_raw'] ?: '—');
                                ?>
                                <tr>
                                    <td><?= e($person['email'] ?: '—') ?></td>
                                    <td><?= e($person['first_name']) ?></td>
                                    <td><?= e($person['last_name']) ?></td>
                                    <td><?= e($person['document_number'] ?: '—') ?></td>
                                    <td><?= e($person['phone'] ?: '—') ?></td>
                                    <td><?= e($person['personal_email'] ?: '—') ?></td>
                                    <td><?= e($person['affiliation_type'] ?: '—') ?></td>
                                    <td><?= e($person['job_title'] ?: $person['specialty'] ?: '—') ?></td>
                                    <td><?= e($person['role_name'] ?: '—') ?></td>
                                    <td><?= e($person['supervisor'] ?: '—') ?></td>
                                    <td><?= e($person['site_name'] ?: '—') ?></td>
                                    <td><?= e($person['site_address'] ?: '—') ?></td>
                                    <td><?= e($person['site_id'] ?: '—') ?></td>
                                    <td><?= e($person['regional'] ?: '—') ?></td>
                                    <td><?= e($person['contract_number'] ?: '—') ?></td>
                                    <td class="date-column" data-filter-date="<?= e($person['contract_end_date'] ?? '') ?>"><?= e($contractEnd) ?></td>
                                    <td><?= e($person['vpn_access'] ?: '—') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
<?php require __DIR__ . '/includes/manager_footer.php'; ?>
