<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/includes/app.php';
require __DIR__ . '/includes/xlsx_directory.php';

if (app_env('APP_ENV') !== 'development' || !in_array('--confirm', $argv ?? [], true)) {
    fwrite(STDERR, "Solo se ejecuta en desarrollo. Define APP_ENV=development y agrega --confirm.\n");
    exit(1);
}

$connection = db();
$connection->beginTransaction();
$converted = 0;
$unrecognized = 0;
$tables = ['administrative_staff', 'instructors'];

try {
    foreach ($tables as $table) {
        $rows = $connection->query(
            "SELECT id, contract_end_date_raw
             FROM {$table}
             WHERE contract_end_date IS NULL
               AND contract_end_date_raw IS NOT NULL
               AND TRIM(contract_end_date_raw) <> ''"
        )->fetchAll();
        $update = $connection->prepare(
            "UPDATE {$table}
             SET contract_end_date = :contract_end_date,
                 contract_end_date_raw = NULL,
                 invalid_contract_date = 0
             WHERE id = :id"
        );

        foreach ($rows as $row) {
            $date = directory_parse_excel_date((string) $row['contract_end_date_raw']);
            if ($date === null) {
                $unrecognized++;
                continue;
            }

            $update->execute(['contract_end_date' => $date, 'id' => $row['id']]);
            $converted++;
        }
    }

    $connection->commit();
} catch (Throwable $exception) {
    if ($connection->inTransaction()) {
        $connection->rollBack();
    }
    fwrite(STDERR, 'No se pudieron normalizar las fechas: ' . $exception->getMessage() . "\n");
    exit(1);
}

fwrite(STDOUT, "Fechas normalizadas: {$converted}\n");
fwrite(STDOUT, "Fechas que requieren revisión manual: {$unrecognized}\n");
if ($unrecognized > 0) {
    exit(2);
}
