<?php
declare(strict_types=1);
require __DIR__ . '/includes/app.php';
require_manager();

$connection = db();
$administrativeCount = (int) $connection->query('SELECT COUNT(*) FROM administrative_staff')->fetchColumn();
$instructorCount = (int) $connection->query('SELECT COUNT(*) FROM instructors')->fetchColumn();
$stats = [
    'total_staff' => $administrativeCount + $instructorCount,
    'administrative_staff' => $administrativeCount,
    'instructors' => $instructorCount,
    'with_contract_number' => (int) $connection->query(
        "SELECT
            (SELECT COUNT(*) FROM administrative_staff WHERE contract_number IS NOT NULL AND TRIM(contract_number) <> '')
            + (SELECT COUNT(*) FROM instructors WHERE contract_number IS NOT NULL AND TRIM(contract_number) <> '')"
    )->fetchColumn(),
    'ending_soon' => (int) $connection->query(
        'SELECT
            (SELECT COUNT(*) FROM administrative_staff
             WHERE contract_end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY))
            + (SELECT COUNT(*) FROM instructors
               WHERE contract_end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY))'
    )->fetchColumn(),
    'end_dates_recorded' => (int) $connection->query(
        'SELECT
            (SELECT COUNT(*) FROM administrative_staff WHERE contract_end_date IS NOT NULL)
            + (SELECT COUNT(*) FROM instructors WHERE contract_end_date IS NOT NULL)'
    )->fetchColumn(),
    'past_end_dates' => (int) $connection->query(
        'SELECT
            (SELECT COUNT(*) FROM administrative_staff WHERE contract_end_date < CURDATE())
            + (SELECT COUNT(*) FROM instructors WHERE contract_end_date < CURDATE())'
    )->fetchColumn(),
    'sites' => (int) $connection->query(
        "SELECT COUNT(*)
         FROM (
            SELECT NULLIF(TRIM(site_name), '') AS site_name FROM administrative_staff
            UNION
            SELECT NULLIF(TRIM(site_name), '') AS site_name FROM instructors
         ) AS directory_sites
         WHERE site_name IS NOT NULL"
    )->fetchColumn(),
];
$regionalMetrics = $connection->query(
    "SELECT regional, SUM(administrative_count) AS administrative_count,
            SUM(instructor_count) AS instructor_count,
            SUM(administrative_count + instructor_count) AS total
     FROM (
        SELECT regional, COUNT(*) AS administrative_count, 0 AS instructor_count
        FROM administrative_staff
        WHERE regional IS NOT NULL AND TRIM(regional) <> ''
        GROUP BY regional
        UNION ALL
        SELECT regional, 0 AS administrative_count, COUNT(*) AS instructor_count
        FROM instructors
        WHERE regional IS NOT NULL AND TRIM(regional) <> ''
        GROUP BY regional
     ) AS regional_totals
     GROUP BY regional
     ORDER BY total DESC, regional"
)->fetchAll();
$affiliationMetrics = $connection->query(
    "SELECT affiliation_type, COUNT(*) AS total
     FROM (
        SELECT NULLIF(TRIM(affiliation_type), '') AS affiliation_type FROM administrative_staff
        UNION ALL
        SELECT NULLIF(TRIM(affiliation_type), '') AS affiliation_type FROM instructors
     ) AS affiliation_totals
     WHERE affiliation_type IS NOT NULL
     GROUP BY affiliation_type
     ORDER BY total DESC, affiliation_type
     LIMIT 8"
)->fetchAll();
$maxAffiliationCount = $affiliationMetrics ? max(array_map('intval', array_column($affiliationMetrics, 'total'))) : 1;
$flash = take_flash();
$managerPageTitle = 'Principal';
$activeManagerPage = 'principal';
require __DIR__ . '/includes/manager_header.php';
?>
            <section class="page-heading dashboard-hero">
                <div>
                    <p class="eyebrow">GESTIÓN DEL TALENTO HUMANO</p>
                    <h1>Principal</h1>
                    <p class="muted">Indicadores del directorio administrativo e instructor.</p>
                </div>
                <a class="button button-primary" href="importar_directorio.php">↑ Importar directorio Excel</a>
            </section>

            <?php if ($flash): ?>
                <div class="alert <?= $flash['type'] === 'error' ? 'alert-error' : 'alert-success' ?>" role="status"><?= e($flash['message']) ?></div>
            <?php endif; ?>

            <section class="stats-grid" aria-label="Métricas del personal">
                <article class="stat-card">
                    <div class="stat-icon stat-green">◉</div>
                    <div><p>Total de personas</p><strong><?= e($stats['total_staff']) ?></strong></div>
                </article>
                <a class="stat-card stat-card-link" href="administrativos.php">
                    <div class="stat-icon stat-blue">▦</div>
                    <div><p>Administrativos</p><strong><?= e($stats['administrative_staff']) ?></strong></div>
                    <span class="stat-note">Ver lista</span>
                </a>
                <a class="stat-card stat-card-link" href="instructores.php">
                    <div class="stat-icon stat-green">◎</div>
                    <div><p>Instructores</p><strong><?= e($stats['instructors']) ?></strong></div>
                    <span class="stat-note">Ver lista</span>
                </a>
                <article class="stat-card">
                    <div class="stat-icon stat-blue">▤</div>
                    <div><p>Con número de contrato</p><strong><?= e($stats['with_contract_number']) ?></strong></div>
                </article>
                <article class="stat-card">
                    <div class="stat-icon stat-purple">⌂</div>
                    <div><p>Sedes registradas</p><strong><?= e($stats['sites']) ?></strong></div>
                </article>
                <article class="stat-card">
                    <div class="stat-icon stat-green">▣</div>
                    <div><p>Fechas de terminación registradas</p><strong><?= e($stats['end_dates_recorded']) ?></strong></div>
                </article>
                <article class="stat-card">
                    <div class="stat-icon stat-amber">◷</div>
                    <div><p>Terminación en 30 días</p><strong><?= e($stats['ending_soon']) ?></strong></div>
                </article>
                <article class="stat-card">
                    <div class="stat-icon stat-red">⌛</div>
                    <div><p>Fechas de fin anteriores a hoy</p><strong><?= e($stats['past_end_dates']) ?></strong></div>
                </article>
            </section>

            <div class="dashboard-metrics-grid">
                <section class="panel">
                    <div class="panel-heading">
                        <div>
                            <p class="eyebrow">DISTRIBUCIÓN DEL PERSONAL</p>
                            <h2>Personas por regional</h2>
                        </div>
                    </div>
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>Regional</th><th>Administrativos</th><th>Instructores</th><th>Total</th></tr></thead>
                            <tbody>
                            <?php if (!$regionalMetrics): ?>
                                <tr><td class="empty-state" colspan="4">No hay regionales disponibles en el directorio.</td></tr>
                            <?php else: ?>
                                <?php foreach ($regionalMetrics as $metric): ?>
                                    <tr>
                                        <td><?= e($metric['regional']) ?></td>
                                        <td><?= e($metric['administrative_count']) ?></td>
                                        <td><?= e($metric['instructor_count']) ?></td>
                                        <td><strong><?= e($metric['total']) ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="panel">
                    <div class="panel-heading">
                        <div>
                            <p class="eyebrow">TIPO DE VINCULACIÓN</p>
                            <h2>Personas por tipo</h2>
                        </div>
                    </div>
                    <div class="metric-list">
                        <?php if (!$affiliationMetrics): ?>
                            <p class="empty-state">No hay tipos de vinculación disponibles en el directorio.</p>
                        <?php else: ?>
                            <?php foreach ($affiliationMetrics as $metric): ?>
                                <div class="metric-item">
                                    <div class="metric-label"><span><?= e($metric['affiliation_type']) ?></span><strong><?= e($metric['total']) ?></strong></div>
                                    <progress class="metric-progress" value="<?= e($metric['total']) ?>" max="<?= e($maxAffiliationCount) ?>" aria-label="<?= e($metric['affiliation_type']) ?>: <?= e($metric['total']) ?>"></progress>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>
            </div>

            <p class="dashboard-note">Las métricas se calculan con los datos del directorio importado. Las fechas próximas corresponden a los siguientes 30 días; las fechas anteriores a hoy se muestran como referencia y no indican por sí solas el estado del contrato.</p>
<?php require __DIR__ . '/includes/manager_footer.php'; ?>
