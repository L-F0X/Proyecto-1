<?php
declare(strict_types=1);
require __DIR__ . '/includes/app.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Método no permitido.');
}

verify_csrf();
$category = is_string($_POST['category'] ?? null) ? $_POST['category'] : '';
$action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';
$categories = [
    'administrative' => ['table' => 'administrative_staff', 'page' => 'administrativos.php'],
    'instructor' => ['table' => 'instructors', 'page' => 'instructores.php'],
];
$redirectPage = $categories[$category]['page'] ?? 'index.php';
$staffId = filter_var($_POST['staff_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$classificationField = $category === 'administrative' ? 'dependency' : 'area';

try {
    if (!isset($categories[$category]) || !in_array($action, ['create', 'update', 'delete'], true)) {
        throw new DomainException('La acción solicitada no es válida.');
    }
    if ($action !== 'create' && $staffId === false) {
        throw new DomainException('No se encontró el registro seleccionado.');
    }

    $readText = static function (string $field, int $maxLength, bool $required = false): ?string {
        $input = $_POST[$field] ?? '';
        $value = is_string($input) ? trim($input) : '';
        if ($required && $value === '') {
            throw new DomainException('Completa los nombres y apellidos.');
        }
        if (mb_strlen($value) > $maxLength || preg_match('/[\x00-\x1F\x7F]/', $value)) {
            throw new DomainException('Uno de los campos supera su longitud permitida o contiene caracteres inválidos.');
        }

        return $value === '' ? null : $value;
    };

    $connection = db();
    $values = [];
    if ($action !== 'delete') {
        $lengths = [
            'document_number' => 50,
            'first_name' => 100,
            'last_name' => 100,
            'email' => 190,
            'phone' => 40,
            'personal_email' => 190,
            'affiliation_type' => 100,
            'job_title' => 150,
            $classificationField => 50,
        ];
        if ($category === 'administrative') {
            $lengths['supervisor'] = 150;
            $lengths['contract_number'] = 80;
        }
        foreach ($lengths as $field => $maxLength) {
            $values[$field] = $readText($field, $maxLength, in_array($field, ['first_name', 'last_name'], true));
        }
        foreach (['email', 'personal_email'] as $emailField) {
            if ($values[$emailField] !== null && filter_var($values[$emailField], FILTER_VALIDATE_EMAIL) === false) {
                throw new DomainException('Escribe un correo electrónico válido o deja el campo vacío.');
            }
        }
        if ($values[$classificationField] === null) {
            $values[$classificationField] = '';
        }
        if ($action === 'create') {
            $values['job_title'] = $category === 'administrative' ? 'Administrativo' : 'Instructor';
        }

        foreach ([
            'contract_start_date' => 'La fecha de inicio no es válida.',
            'contract_end_date' => 'La fecha de terminación no es válida.',
        ] as $dateField => $errorMessage) {
            $dateInput = $_POST[$dateField] ?? '';
            $dateInput = is_string($dateInput) ? trim($dateInput) : '';
            if ($dateInput !== '') {
                $parsedDate = DateTimeImmutable::createFromFormat('!Y-m-d', $dateInput);
                $dateErrors = DateTimeImmutable::getLastErrors();
                if (!$parsedDate
                    || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))
                    || $parsedDate->format('Y-m-d') !== $dateInput) {
                    throw new DomainException($errorMessage);
                }
            }
            $values[$dateField] = $dateInput === '' ? null : $dateInput;
        }
    }

    $table = $categories[$category]['table'];
    $connection->beginTransaction();
    if ($action === 'create') {
        $columns = [
            'first_name', 'last_name', 'email', 'document_number', 'phone', 'personal_email',
            'affiliation_type', 'job_title', $classificationField, 'contract_start_date', 'contract_end_date',
        ];
        if ($category === 'administrative') {
            $columns = array_merge($columns, ['supervisor', 'contract_number']);
        }
        $columnSql = implode(', ', $columns);
        $valueSql = implode(', ', array_map(static fn (string $column): string => ':' . $column, $columns));
        $statement = $connection->prepare("INSERT INTO {$table} ({$columnSql}) VALUES ({$valueSql})");
        $statement->execute(array_intersect_key($values, array_flip($columns)));
        $successMessage = 'El registro se añadió correctamente.';
    } elseif ($action === 'update') {
        $existingStatement = $connection->prepare(
            "SELECT job_title, specialty, contract_end_date, contract_end_date_raw, invalid_contract_date
             FROM {$table}
             WHERE id = :staff_id
             FOR UPDATE"
        );
        $existingStatement->execute(['staff_id' => $staffId]);
        $existing = $existingStatement->fetch();
        if (!$existing) {
            throw new DomainException('No se encontró el registro seleccionado.');
        }

        $existingJobTitle = $existing['job_title'];
        if ($category === 'administrative' && ($existingJobTitle === null || $existingJobTitle === '')) {
            $existingJobTitle = $existing['specialty'];
        }
        if (in_array(mb_strtolower(trim((string) $existingJobTitle)), ['administrativo', 'instructor'], true)) {
            $values['job_title'] = trim((string) $existingJobTitle);
        }

        $rawDate = null;
        $invalidDate = 0;
        if ($values['contract_end_date'] === null
            && $existing['contract_end_date'] === null
            && $existing['contract_end_date_raw'] !== null) {
            $rawDate = $existing['contract_end_date_raw'];
            $invalidDate = (int) $existing['invalid_contract_date'];
        }
        $values['contract_end_date_raw'] = $rawDate;
        $values['invalid_contract_date'] = $invalidDate;

        $updateFields = [
            'first_name', 'last_name', 'email', 'document_number', 'phone', 'personal_email',
            'affiliation_type', 'job_title', $classificationField, 'contract_start_date', 'contract_end_date',
            'contract_end_date_raw', 'invalid_contract_date',
        ];
        if ($category === 'administrative') {
            $updateFields = array_merge($updateFields, ['supervisor', 'contract_number']);
            $values['specialty'] = null;
            $updateFields[] = 'specialty';
        }
        $assignments = implode(', ', array_map(static fn (string $field): string => "{$field} = :{$field}", $updateFields));
        $statement = $connection->prepare("UPDATE {$table} SET {$assignments} WHERE id = :staff_id");
        $values['staff_id'] = $staffId;
        $statement->execute(array_intersect_key($values, array_flip(array_merge($updateFields, ['staff_id']))));
        $successMessage = 'Los cambios se guardaron correctamente.';
    } else {
        $statement = $connection->prepare("DELETE FROM {$table} WHERE id = :staff_id");
        $statement->execute(['staff_id' => $staffId]);
        if ($statement->rowCount() !== 1) {
            throw new DomainException('No se encontró el registro seleccionado.');
        }
        $successMessage = 'El registro se eliminó correctamente.';
    }
    $connection->commit();
    set_flash($successMessage);
} catch (DomainException $exception) {
    if (isset($connection) && $connection->inTransaction()) {
        $connection->rollBack();
    }
    set_flash($exception->getMessage(), 'error');
} catch (PDOException $exception) {
    if (isset($connection) && $connection->inTransaction()) {
        $connection->rollBack();
    }
    error_log('Error al gestionar un registro del directorio: ' . $exception->getMessage());
    $driverErrorCode = $exception->errorInfo[1] ?? null;
    if ($action === 'delete' && $category === 'instructor' && $driverErrorCode === 1451) {
        set_flash('No se puede eliminar este instructor mientras tenga una cuenta de usuario vinculada. Cambia o elimina primero esa cuenta.', 'error');
    } else {
        set_flash('No se pudo completar la operación. Revisa los datos e inténtalo de nuevo.', 'error');
    }
} catch (Throwable $exception) {
    if (isset($connection) && $connection->inTransaction()) {
        $connection->rollBack();
    }
    error_log('Error al gestionar un registro del directorio: ' . $exception->getMessage());
    set_flash('No se pudo completar la operación. Inténtalo de nuevo.', 'error');
}

header('Location: ' . $redirectPage);
exit;
