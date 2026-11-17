<?php
declare(strict_types=1);
require __DIR__ . '/includes/app.php';
require __DIR__ . '/includes/xlsx_directory.php';
require_manager();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $upload = $_FILES['directory_file'] ?? null;
    if (!is_array($upload) || ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $error = 'Selecciona un archivo Excel válido.';
    } elseif (!isset($_POST['confirm_import']) || $_POST['confirm_import'] !== 'yes') {
        $error = 'Confirma que deseas importar las dos listas separadas.';
    } elseif (!isset($upload['name'], $upload['tmp_name'], $upload['size'])
        || strtolower(pathinfo((string) $upload['name'], PATHINFO_EXTENSION)) !== 'xlsx'
        || (int) $upload['size'] < 1
        || (int) $upload['size'] > 5 * 1024 * 1024
        || !is_uploaded_file((string) $upload['tmp_name'])) {
        $error = 'El archivo debe ser un .xlsx válido de máximo 5 MB.';
    } else {
        $connection = null;
        try {
            $workbook = read_directory_workbook((string) $upload['tmp_name']);
            $administrativeRows = $workbook['records']['Administrativos'];
            $instructorRows = $workbook['records']['Instructores'];
            if (!$administrativeRows && !$instructorRows) {
                throw new RuntimeException('No se encontraron filas clasificables en las hojas requeridas.');
            }

            $connection = db();
            $connection->beginTransaction();
            $existingImports = (int) $connection->query(
                'SELECT
                    (SELECT COUNT(*) FROM instructors WHERE source_row IS NOT NULL)
                    + (SELECT COUNT(*) FROM administrative_staff WHERE source_row IS NOT NULL)'
            )->fetchColumn();
            if ($existingImports > 0) {
                throw new RuntimeException('Ya hay filas importadas desde Excel. No se repitió la importación para evitar sobrescribir registros.');
            }

            $columns = [
                'first_name', 'last_name', 'email', 'specialty', 'document_number',
                'phone', 'personal_email', 'affiliation_type', 'job_title', 'role_name',
                'supervisor', 'site_name', 'site_address', 'site_id', 'regional',
                'contract_number', 'contract_end_date', 'contract_end_date_raw',
                'vpn_access', 'extension_ip', 'source_row', 'duplicate_in_source',
                'duplicate_with_other_category', 'invalid_contract_date',
            ];
            $columnSql = implode(', ', $columns);
            $valueSql = implode(', ', array_map(static fn (string $column): string => ':' . $column, $columns));
            $instructorInsert = $connection->prepare("INSERT INTO instructors ({$columnSql}) VALUES ({$valueSql})");
            $administrativeInsert = $connection->prepare("INSERT INTO administrative_staff ({$columnSql}) VALUES ({$valueSql})");

            foreach ($instructorRows as $record) {
                $instructorInsert->execute($record);
            }
            foreach ($administrativeRows as $record) {
                $administrativeInsert->execute($record);
            }
            $connection->commit();

            $countDuplicates = static fn (array $rows, string $key): int => count(array_filter(
                $rows,
                static fn (array $row): bool => $row[$key]
            ));
            $report = $workbook['report'];
            set_flash(sprintf(
                'Importación completa: %d administrativos y %d instructores. Omitidas: %d filas sin rol y %d incompletas. Revisión: %d filas con duplicados en su hoja, %d coincidencias entre categorías, %d correos institucionales vacíos y %d fechas de terminación no reconocidas.',
                count($administrativeRows),
                count($instructorRows),
                $report['skipped_unclassified'],
                $report['skipped_incomplete'],
                $countDuplicates($administrativeRows, 'duplicate_in_source') + $countDuplicates($instructorRows, 'duplicate_in_source'),
                $countDuplicates($administrativeRows, 'duplicate_with_other_category') + $countDuplicates($instructorRows, 'duplicate_with_other_category'),
                $report['missing_institutional_email'],
                $report['invalid_contract_dates']
            ));
            header('Location: index.php');
            exit;
        } catch (PDOException $exception) {
            if ($connection instanceof PDO && $connection->inTransaction()) {
                $connection->rollBack();
            }
            error_log('Error al importar el directorio de personal: ' . $exception->getCode());
            $error = 'No se pudo guardar el directorio. Verifica que hayas aplicado la migración de separación de categorías.';
        } catch (RuntimeException $exception) {
            if ($connection instanceof PDO && $connection->inTransaction()) {
                $connection->rollBack();
            }
            $error = $exception->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Importar directorio | SENA</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">
    <main class="auth-card import-card">
        <a class="brand brand-auth" href="index.php">
            <span class="brand-mark">S</span>
            <span><strong>SENA</strong><small>Talento humano</small></span>
        </a>
        <p class="eyebrow">DIRECTORIO DE PERSONAL</p>
        <h1>Importar Excel</h1>
        <p class="muted">Se leerán únicamente las hojas «Administrativos» e «Instructores». No se procesará «Funcionarios Planta» ni las hojas auxiliares.</p>
        <div class="import-summary">
            <strong>Separación acordada</strong>
            <ul>
                <li>Importar cada lista en su propia categoría.</li>
                <li>Omitir filas sin rol y conservar coincidencias señalándolas para revisión.</li>
                <li>Los correos vacíos y fechas no reconocidas se reportarán; no se muestran documentos ni datos personales en el resultado.</li>
            </ul>
        </div>
        <?php if ($error !== ''): ?>
            <div class="alert alert-error" role="alert"><?= e($error) ?></div>
        <?php endif; ?>
        <form method="post" enctype="multipart/form-data" class="form-stack">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <label for="directory_file">Libro Excel (.xlsx, máximo 5 MB)</label>
            <input id="directory_file" name="directory_file" type="file" accept=".xlsx" required>
            <label class="import-confirm">
                <input type="checkbox" name="confirm_import" value="yes" required>
                <span>Confirmo importar las hojas y con las reglas indicadas.</span>
            </label>
            <button class="button button-primary button-full" type="submit">Separar e importar directorio</button>
        </form>
        <p class="auth-footnote"><a href="index.php">Volver a Principal</a></p>
    </main>
</body>
</html>
