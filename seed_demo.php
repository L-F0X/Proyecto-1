<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

if (getenv('APP_ENV') !== 'development' || !in_array('--confirm', $argv ?? [], true)) {
    fwrite(STDERR, "Solo se ejecuta en desarrollo. Define APP_ENV=development y agrega --confirm.\n");
    exit(1);
}

require __DIR__ . '/includes/app.php';

$today = new DateTimeImmutable('today');
$instructorData = [
    [
        'first_name' => 'Lucía',
        'last_name' => 'Ramírez',
        'email' => 'lucia.ramirez@sena.test',
        'specialty' => 'Diseño y producción multimedia',
        'contract_number' => 'DEMO-2026-001',
        'contract_type' => 'Prestación de servicios',
        'start_date' => $today->modify('-150 days')->format('Y-m-d'),
        'end_date' => $today->modify('+18 days')->format('Y-m-d'),
        'monthly_value' => '4200000.00',
        'status' => 'Activo',
        'password' => bin2hex(random_bytes(16)),
    ],
    [
        'first_name' => 'Mateo',
        'last_name' => 'Gómez',
        'email' => 'mateo.gomez@sena.test',
        'specialty' => 'Desarrollo de software',
        'contract_number' => 'DEMO-2026-002',
        'contract_type' => 'Prestación de servicios',
        'start_date' => $today->modify('-90 days')->format('Y-m-d'),
        'end_date' => $today->modify('+120 days')->format('Y-m-d'),
        'monthly_value' => '5100000.00',
        'status' => 'Activo',
        'password' => bin2hex(random_bytes(16)),
    ],
    [
        'first_name' => 'Valentina',
        'last_name' => 'Cruz',
        'email' => 'valentina.cruz@sena.test',
        'specialty' => 'Gestión administrativa',
        'contract_number' => 'DEMO-2026-003',
        'contract_type' => 'Prestación de servicios',
        'start_date' => $today->modify('-180 days')->format('Y-m-d'),
        'end_date' => $today->modify('-5 days')->format('Y-m-d'),
        'monthly_value' => '3900000.00',
        'status' => 'Finalizado',
        'password' => bin2hex(random_bytes(16)),
    ],
];

$managerAccounts = [
    [
        'full_name' => 'Administración de prueba',
        'email' => 'admin.demo@sena.test',
        'role' => 'admin',
        'password' => bin2hex(random_bytes(16)),
    ],
    [
        'full_name' => 'Talento Humano de prueba',
        'email' => 'talento.demo@sena.test',
        'role' => 'talento_humano',
        'password' => bin2hex(random_bytes(16)),
    ],
];

$connection = db();
$connection->beginTransaction();

try {
    $findInstructor = $connection->prepare('SELECT id FROM instructors WHERE email = :email');
    $createInstructor = $connection->prepare(
        'INSERT INTO instructors (first_name, last_name, email, specialty)
         VALUES (:first_name, :last_name, :email, :specialty)
         ON DUPLICATE KEY UPDATE first_name = VALUES(first_name), last_name = VALUES(last_name), specialty = VALUES(specialty)'
    );
    $instructorIds = [];
    foreach ($instructorData as $instructor) {
        $createInstructor->execute([
            'first_name' => $instructor['first_name'],
            'last_name' => $instructor['last_name'],
            'email' => $instructor['email'],
            'specialty' => $instructor['specialty'],
        ]);
        $findInstructor->execute(['email' => $instructor['email']]);
        $instructorIds[$instructor['email']] = (int) $findInstructor->fetchColumn();
    }

    $createContract = $connection->prepare(
        'INSERT INTO contracts (instructor_id, contract_number, contract_type, start_date, end_date, monthly_value, status)
         VALUES (:instructor_id, :contract_number, :contract_type, :start_date, :end_date, :monthly_value, :status)
         ON DUPLICATE KEY UPDATE instructor_id = VALUES(instructor_id), contract_type = VALUES(contract_type),
             start_date = VALUES(start_date), end_date = VALUES(end_date), monthly_value = VALUES(monthly_value), status = VALUES(status)'
    );
    foreach ($instructorData as $instructor) {
        $createContract->execute([
            'instructor_id' => $instructorIds[$instructor['email']],
            'contract_number' => $instructor['contract_number'],
            'contract_type' => $instructor['contract_type'],
            'start_date' => $instructor['start_date'],
            'end_date' => $instructor['end_date'],
            'monthly_value' => $instructor['monthly_value'],
            'status' => $instructor['status'],
        ]);
    }

    $createUser = $connection->prepare(
        'INSERT INTO users (full_name, email, password_hash, role, instructor_id)
         VALUES (:full_name, :email, :password_hash, :role, :instructor_id)
         ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), password_hash = VALUES(password_hash),
             role = VALUES(role), instructor_id = VALUES(instructor_id)'
    );
    foreach ($managerAccounts as $account) {
        $createUser->execute([
            'full_name' => $account['full_name'],
            'email' => $account['email'],
            'password_hash' => password_hash($account['password'], PASSWORD_DEFAULT),
            'role' => $account['role'],
            'instructor_id' => null,
        ]);
    }
    foreach ($instructorData as $instructor) {
        $createUser->execute([
            'full_name' => $instructor['first_name'] . ' ' . $instructor['last_name'],
            'email' => $instructor['email'],
            'password_hash' => password_hash($instructor['password'], PASSWORD_DEFAULT),
            'role' => 'instructor',
            'instructor_id' => $instructorIds[$instructor['email']],
        ]);
    }

    $connection->commit();
    echo "Datos demo creados/actualizados. Cuentas de acceso (solo desarrollo):\n";
    foreach ($managerAccounts as $account) {
        echo $account['role'] . ' | ' . $account['email'] . ' | ' . $account['password'] . "\n";
    }
    foreach ($instructorData as $instructor) {
        echo 'instructor | ' . $instructor['email'] . ' | ' . $instructor['password'] . "\n";
    }
} catch (Throwable $exception) {
    if ($connection->inTransaction()) {
        $connection->rollBack();
    }
    fwrite(STDERR, 'No se pudieron cargar los datos demo: ' . $exception->getMessage() . "\n");
    exit(1);
}
