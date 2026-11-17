<?php
declare(strict_types=1);
require __DIR__ . '/includes/app.php';
require_manager();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Método no permitido.');
}

verify_csrf();
$action = (string) ($_POST['action'] ?? '');
$connection = db();

try {
    if ($action === 'create_contract') {
        $instructorId = filter_var($_POST['instructor_id'] ?? null, FILTER_VALIDATE_INT);
        $number = trim((string) ($_POST['contract_number'] ?? ''));
        $type = trim((string) ($_POST['contract_type'] ?? ''));
        $startDate = (string) ($_POST['start_date'] ?? '');
        $endDate = (string) ($_POST['end_date'] ?? '');
        $amountInput = (string) ($_POST['monthly_value'] ?? '');
        $amount = filter_var($amountInput, FILTER_VALIDATE_FLOAT);

        $start = DateTime::createFromFormat('!Y-m-d', $startDate);
        $end = DateTime::createFromFormat('!Y-m-d', $endDate);
        if (!$instructorId || $number === '' || mb_strlen($number) > 50 || $type === '' || mb_strlen($type) > 100) {
            throw new InvalidArgumentException('Completa los datos del contrato.');
        }
        if (!$start || $start->format('Y-m-d') !== $startDate || !$end || $end->format('Y-m-d') !== $endDate || $end < $start) {
            throw new InvalidArgumentException('Verifica las fechas del contrato.');
        }
        if ($amount === false || $amount < 0 || $amount > 999999999.99) {
            throw new InvalidArgumentException('Ingresa un valor mensual válido.');
        }

        $statement = $connection->prepare(
            'INSERT INTO contracts (instructor_id, contract_number, contract_type, start_date, end_date, monthly_value, status)
             VALUES (:instructor_id, :contract_number, :contract_type, :start_date, :end_date, :monthly_value, :status)'
        );
        $statement->execute([
            'instructor_id' => $instructorId,
            'contract_number' => $number,
            'contract_type' => $type,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'monthly_value' => number_format((float) $amount, 2, '.', ''),
            'status' => 'Activo',
        ]);
        set_flash('Contrato registrado correctamente.');
    } elseif ($action === 'update_contract_status') {
        $contractId = filter_var($_POST['contract_id'] ?? null, FILTER_VALIDATE_INT);
        $status = (string) ($_POST['status'] ?? '');
        $allowedStatuses = ['Activo', 'Finalizado', 'Suspendido'];
        if (!$contractId || !in_array($status, $allowedStatuses, true)) {
            throw new InvalidArgumentException('Selecciona un estado válido.');
        }

        $statement = $connection->prepare('UPDATE contracts SET status = :status WHERE id = :id');
        $statement->execute(['status' => $status, 'id' => $contractId]);
        if ($statement->rowCount() === 0) {
            $exists = $connection->prepare('SELECT 1 FROM contracts WHERE id = :id');
            $exists->execute(['id' => $contractId]);
            if (!$exists->fetchColumn()) {
                throw new InvalidArgumentException('No se encontró el contrato solicitado.');
            }
        }
        set_flash('Estado del contrato actualizado.');
    } else {
        throw new InvalidArgumentException('La acción solicitada no es válida.');
    }
} catch (InvalidArgumentException $exception) {
    set_flash($exception->getMessage(), 'error');
} catch (PDOException $exception) {
    error_log('Error al guardar información de personal: ' . $exception->getMessage());
    set_flash('No se pudo guardar la información. Verifica que no exista un correo o número de contrato duplicado.', 'error');
}

header('Location: index.php');
exit;
