<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$localConfig = __DIR__ . '/config.local.php';
if (is_file($localConfig)) {
    require_once $localConfig;
}
$appEnvironment = getenv('APP_ENV');
if ($appEnvironment === false) {
    $appEnvironment = $_SERVER['APP_ENV'] ?? false;
}

if ($appEnvironment !== 'development' || !in_array('--confirm', $argv ?? [], true)) {
    fwrite(STDERR, "Solo se ejecuta en desarrollo. Define APP_ENV=development y agrega --confirm.\n");
    exit(1);
}

require __DIR__ . '/includes/app.php';

$demoPasswords = [];
foreach ([
    'DEMO_ADMIN_PASSWORD',
    'DEMO_HR_PASSWORD',
    'DEMO_LUCIA_PASSWORD',
    'DEMO_MATEO_PASSWORD',
    'DEMO_VALENTINA_PASSWORD',
] as $passwordKey) {
    $password = app_env($passwordKey);
    if ($password === false || $password === '') {
        fwrite(STDERR, 'Configura las contraseñas demo en config.local.php antes de ejecutar el cargador.' . "\n");
        exit(1);
    }
    $demoPasswords[$passwordKey] = $password;
}

$instructorData = [
    [
        'first_name' => 'Lucía',
        'last_name' => 'Ramírez',
        'email' => 'lucia.ramirez@sena.test',
        'specialty' => 'Diseño y producción multimedia',
        'job_title' => 'Instructor',
        'password' => $demoPasswords['DEMO_LUCIA_PASSWORD'],
    ],
    [
        'first_name' => 'Mateo',
        'last_name' => 'Gómez',
        'email' => 'mateo.gomez@sena.test',
        'specialty' => 'Desarrollo de software',
        'job_title' => 'Instructor',
        'password' => $demoPasswords['DEMO_MATEO_PASSWORD'],
    ],
    [
        'first_name' => 'Valentina',
        'last_name' => 'Cruz',
        'email' => 'valentina.cruz@sena.test',
        'specialty' => 'Gestión administrativa',
        'job_title' => 'Instructor',
        'password' => $demoPasswords['DEMO_VALENTINA_PASSWORD'],
    ],
];

$managerAccounts = [
    [
        'full_name' => 'Administración de prueba',
        'email' => 'admin.demo@sena.test',
        'role' => 'admin',
        'password' => $demoPasswords['DEMO_ADMIN_PASSWORD'],
    ],
    [
        'full_name' => 'Talento Humano de prueba',
        'email' => 'talento.demo@sena.test',
        'role' => 'talento_humano',
        'password' => $demoPasswords['DEMO_HR_PASSWORD'],
    ],
];

$connection = db();
$connection->beginTransaction();

try {
    $findInstructor = $connection->prepare('SELECT id FROM instructors WHERE email = :email LIMIT 1');
    $createInstructor = $connection->prepare(
        "INSERT INTO instructors (first_name, last_name, email, specialty, job_title, area)
         VALUES (:first_name, :last_name, :email, :specialty, :job_title, '')"
    );
    $updateInstructor = $connection->prepare(
        "UPDATE instructors SET first_name = :first_name, last_name = :last_name, specialty = :specialty,
             job_title = :job_title, area = ''
         WHERE id = :id"
    );
    $instructorIds = [];
    foreach ($instructorData as $instructor) {
        $instructorValues = [
            'first_name' => $instructor['first_name'],
            'last_name' => $instructor['last_name'],
            'email' => $instructor['email'],
            'specialty' => $instructor['specialty'],
            'job_title' => $instructor['job_title'],
        ];
        $findInstructor->execute(['email' => $instructor['email']]);
        $existingInstructorId = $findInstructor->fetchColumn();
        if ($existingInstructorId === false) {
            $createInstructor->execute($instructorValues);
            $instructorIds[$instructor['email']] = (int) $connection->lastInsertId();
            continue;
        }

        $instructorIds[$instructor['email']] = (int) $existingInstructorId;
        $updateInstructor->execute([
            'first_name' => $instructor['first_name'],
            'last_name' => $instructor['last_name'],
            'specialty' => $instructor['specialty'],
            'job_title' => $instructor['job_title'],
            'id' => $existingInstructorId,
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
    fwrite(STDERR, 'No se pudieron cargar las cuentas demo: ' . $exception->getMessage() . "\n");
    exit(1);
}
