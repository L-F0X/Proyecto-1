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
$action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';
$userId = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$fullNameInput = $_POST['full_name'] ?? '';
$emailInput = $_POST['email'] ?? '';
$roleInput = $_POST['role'] ?? '';
$passwordInput = $_POST['password'] ?? '';
$fullName = is_string($fullNameInput) ? trim($fullNameInput) : '';
$email = is_string($emailInput) ? mb_strtolower(trim($emailInput)) : '';
$role = is_string($roleInput) ? $roleInput : '';
$password = is_string($passwordInput) ? $passwordInput : '';
$allowedRoles = ['admin', 'talento_humano', 'instructor'];

try {
    if (!in_array($action, ['create', 'update', 'delete'], true)) {
        throw new DomainException('La acción solicitada no es válida.');
    }

    if ($action !== 'delete') {
        if ($fullName === '' || mb_strlen($fullName) > 150 || preg_match('/[\x00-\x1F\x7F]/', $fullName)) {
            throw new DomainException('Escribe un nombre válido de máximo 150 caracteres.');
        }
        if (strlen($email) > 190 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new DomainException('Escribe un correo electrónico válido.');
        }
        if (!in_array($role, $allowedRoles, true)) {
            throw new DomainException('Selecciona un perfil de acceso válido.');
        }
        if (($action === 'create' && $password === '') || ($password !== '' && (mb_strlen($password) < 12 || strlen($password) > 72))) {
            throw new DomainException('La contraseña debe tener al menos 12 caracteres y máximo 72 bytes.');
        }
        if ($action === 'update' && $userId === false) {
            throw new DomainException('No se encontró la cuenta que deseas actualizar.');
        }
    } elseif ($userId === false) {
        throw new DomainException('No se encontró la cuenta que deseas eliminar.');
    }

    $connection = db();
    $instructorId = null;
    if ($action !== 'delete' && $role === 'instructor') {
        $instructorId = filter_var($_POST['instructor_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($instructorId === false) {
            throw new DomainException('Selecciona el perfil de instructor que se vinculará a esta cuenta.');
        }
        $instructorStatement = $connection->prepare('SELECT id FROM instructors WHERE id = :instructor_id');
        $instructorStatement->execute(['instructor_id' => $instructorId]);
        if (!$instructorStatement->fetchColumn()) {
            throw new DomainException('El instructor seleccionado no existe.');
        }
    }

    $connection->beginTransaction();
    if ($action === 'create') {
        $statement = $connection->prepare(
            'INSERT INTO users (full_name, email, password_hash, role, instructor_id)
             VALUES (:full_name, :email, :password_hash, :role, :instructor_id)'
        );
        $statement->execute([
            'full_name' => $fullName,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $role,
            'instructor_id' => $instructorId,
        ]);
        $successMessage = 'La cuenta de usuario se creó correctamente.';
    } elseif ($action === 'update') {
        $adminRows = $connection->query("SELECT id FROM users WHERE role = 'admin' FOR UPDATE")->fetchAll(PDO::FETCH_COLUMN);
        $userStatement = $connection->prepare('SELECT id, role FROM users WHERE id = :user_id FOR UPDATE');
        $userStatement->execute(['user_id' => $userId]);
        $existingUser = $userStatement->fetch();
        if (!$existingUser) {
            throw new DomainException('No se encontró la cuenta que deseas actualizar.');
        }
        if ((int) $userId === (int) $_SESSION['user']['id'] && $existingUser['role'] !== $role) {
            throw new DomainException('No puedes cambiar tu propio perfil de acceso.');
        }
        if ($existingUser['role'] === 'admin' && $role !== 'admin' && count($adminRows) <= 1) {
            throw new DomainException('No se puede quitar el último administrador del sistema.');
        }
        if ($instructorId !== null) {
            $linkedStatement = $connection->prepare(
                'SELECT id FROM users
                 WHERE instructor_id = :instructor_id AND id <> :user_id
                 LIMIT 1'
            );
            $linkedStatement->execute(['instructor_id' => $instructorId, 'user_id' => $userId]);
            if ($linkedStatement->fetchColumn()) {
                throw new DomainException('Ese instructor ya está vinculado a otra cuenta.');
            }
        }

        if ($password === '') {
            $statement = $connection->prepare(
                'UPDATE users
                 SET full_name = :full_name, email = :email, role = :role, instructor_id = :instructor_id
                 WHERE id = :user_id'
            );
            $parameters = [
                'full_name' => $fullName,
                'email' => $email,
                'role' => $role,
                'instructor_id' => $instructorId,
                'user_id' => $userId,
            ];
        } else {
            $statement = $connection->prepare(
                'UPDATE users
                 SET full_name = :full_name, email = :email, password_hash = :password_hash,
                     role = :role, instructor_id = :instructor_id
                 WHERE id = :user_id'
            );
            $parameters = [
                'full_name' => $fullName,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'role' => $role,
                'instructor_id' => $instructorId,
                'user_id' => $userId,
            ];
        }
        $statement->execute($parameters);
        $successMessage = 'Los cambios de la cuenta se guardaron correctamente.';
    } else {
        if ((int) $userId === (int) $_SESSION['user']['id']) {
            throw new DomainException('No puedes eliminar tu propia cuenta.');
        }

        $adminRows = $connection->query("SELECT id FROM users WHERE role = 'admin' FOR UPDATE")->fetchAll(PDO::FETCH_COLUMN);
        $userStatement = $connection->prepare('SELECT id, role FROM users WHERE id = :user_id FOR UPDATE');
        $userStatement->execute(['user_id' => $userId]);
        $existingUser = $userStatement->fetch();
        if (!$existingUser) {
            throw new DomainException('No se encontró la cuenta que deseas eliminar.');
        }
        if ($existingUser['role'] === 'admin' && count($adminRows) <= 1) {
            throw new DomainException('No se puede eliminar el último administrador del sistema.');
        }

        $statement = $connection->prepare('DELETE FROM users WHERE id = :user_id');
        $statement->execute(['user_id' => $userId]);
        $successMessage = 'La cuenta de usuario se eliminó correctamente.';
    }
    $connection->commit();
    if ($action === 'update' && (int) $userId === (int) $_SESSION['user']['id']) {
        $_SESSION['user']['name'] = $fullName;
        $_SESSION['user']['email'] = $email;
    }
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
    if ($exception->getCode() === '23000') {
        set_flash('El correo o el instructor ya está asociado a otra cuenta.', 'error');
    } else {
        error_log('Error al gestionar una cuenta de usuario: ' . $exception->getMessage());
        set_flash('No se pudo completar la operación. Inténtalo de nuevo.', 'error');
    }
} catch (Throwable $exception) {
    if (isset($connection) && $connection->inTransaction()) {
        $connection->rollBack();
    }
    error_log('Error al gestionar una cuenta de usuario: ' . $exception->getMessage());
    set_flash('No se pudo completar la operación. Inténtalo de nuevo.', 'error');
}

header('Location: usuarios.php');
exit;
