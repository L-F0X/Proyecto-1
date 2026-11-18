<?php
declare(strict_types=1);
require __DIR__ . '/includes/app.php';
require_admin();

$users = db()->query(
    'SELECT id, full_name, email, role, instructor_id, created_at
     FROM users u
     ORDER BY full_name, id'
)->fetchAll();
$instructors = db()->query(
    'SELECT i.id, i.first_name, i.last_name, i.document_number, u.id AS linked_user_id
     FROM instructors i
     LEFT JOIN users u ON u.instructor_id = i.id
     ORDER BY i.last_name, i.first_name'
)->fetchAll();
$flash = take_flash();
$roleLabels = [
    'admin' => 'Administrador',
    'talento_humano' => 'Talento Humano',
    'instructor' => 'Analista',
];
$managerPageTitle = 'Usuarios';
$activeManagerPage = 'usuarios';
require __DIR__ . '/includes/manager_header.php';
?>
            <section class="page-heading">
                <div>
                    <p class="eyebrow">ADMINISTRACIÓN DE ACCESOS</p>
                    <h1>Usuarios</h1>
                    <p class="muted">Crea, edita y elimina cuentas de acceso al sistema.</p>
                </div>
                <button class="button button-primary" type="button" data-open-dialog="create-user-dialog">+ Añadir usuario</button>
            </section>

            <?php if ($flash): ?>
                <div class="alert <?= $flash['type'] === 'error' ? 'alert-error' : 'alert-success' ?>" role="<?= $flash['type'] === 'error' ? 'alert' : 'status' ?>"><?= e($flash['message']) ?></div>
            <?php endif; ?>

            <section class="panel">
                <div class="panel-heading">
                    <div><p class="eyebrow">ACCESO AL SISTEMA</p><h2>Cuentas registradas <span class="count-pill"><?= e(count($users)) ?></span></h2></div>
                </div>
                <div class="table-wrap">
                    <table class="directory-table">
                        <thead>
                            <tr>
                                <th scope="col">Nombre</th>
                                <th scope="col">Correo</th>
                                <th scope="col">Perfil</th>
                                <th scope="col">Creado</th>
                                <th scope="col">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!$users): ?>
                            <tr><td class="empty-state" colspan="5">No hay cuentas de usuario registradas.</td></tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <?php $userId = (string) $user['id']; ?>
                                <tr>
                                    <td><?= e($user['full_name']) ?></td>
                                    <td><?= e($user['email']) ?></td>
                                    <td><?= e($roleLabels[$user['role']] ?? $user['role']) ?></td>
                                    <td><?= e(date('d/m/Y', strtotime($user['created_at']))) ?></td>
                                    <td>
                                        <div class="user-actions">
                                            <button class="button-small" type="button"
                                                data-edit-user
                                                data-user-id="<?= e($userId) ?>"
                                                data-full-name="<?= e($user['full_name']) ?>"
                                                data-email="<?= e($user['email']) ?>"
                                                data-role="<?= e($user['role']) ?>"
                                                data-instructor-id="<?= e($user['instructor_id'] ?? '') ?>">Editar</button>
                                            <form action="user_actions.php" method="post" data-confirm-user-delete>
                                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="user_id" value="<?= e($userId) ?>">
                                                <button class="button-small button-danger" type="submit" <?= (int) $user['id'] === (int) $_SESSION['user']['id'] ? 'disabled title="No puedes eliminar tu propia cuenta."' : '' ?>>Eliminar</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <dialog class="form-dialog" id="create-user-dialog">
                <form class="dialog-form" action="user_actions.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="create">
                    <div class="dialog-heading">
                        <div><p class="eyebrow">NUEVA CUENTA</p><h2>Añadir usuario</h2></div>
                        <button class="dialog-close" type="button" data-close-dialog aria-label="Cerrar">×</button>
                    </div>
                    <label for="create-full-name">Nombre completo</label>
                    <input id="create-full-name" name="full_name" type="text" maxlength="150" autocomplete="name" required>
                    <label for="create-email">Correo</label>
                    <input id="create-email" name="email" type="email" maxlength="190" autocomplete="email" required>
                    <label for="create-role">Perfil</label>
                    <select id="create-role" name="role" data-user-role required>
                        <option value="talento_humano">Talento Humano</option>
                        <option value="admin">Administrador</option>
                        <option value="instructor">Analista</option>
                    </select>
                    <div data-instructor-field hidden>
                        <label for="create-instructor-id">Instructor vinculado</label>
                        <select id="create-instructor-id" name="instructor_id" data-instructor-select disabled>
                            <option value="">Selecciona un instructor</option>
                            <?php foreach ($instructors as $instructor): ?>
                                <?php $instructorOption = trim($instructor['first_name'] . ' ' . $instructor['last_name']); ?>
                                <option value="<?= e($instructor['id']) ?>" data-linked-user-id="<?= e($instructor['linked_user_id'] ?? '') ?>" <?= $instructor['linked_user_id'] !== null ? 'disabled' : '' ?>>
                                    <?= e($instructorOption . ($instructor['document_number'] ? ' · ' . $instructor['document_number'] : '')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <label for="create-password">Contraseña inicial (mínimo 12 caracteres)</label>
                    <input id="create-password" name="password" type="password" minlength="12" autocomplete="new-password" required>
                    <div class="dialog-actions">
                        <button class="button button-secondary" type="button" data-close-dialog>Cancelar</button>
                        <button class="button button-primary" type="submit">Crear usuario</button>
                    </div>
                </form>
            </dialog>

            <dialog class="form-dialog" id="edit-user-dialog">
                <form class="dialog-form" action="user_actions.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="user_id" data-edit-user-id>
                    <div class="dialog-heading">
                        <div><p class="eyebrow">ACTUALIZAR CUENTA</p><h2>Editar usuario</h2></div>
                        <button class="dialog-close" type="button" data-close-dialog aria-label="Cerrar">×</button>
                    </div>
                    <label for="edit-full-name">Nombre completo</label>
                    <input id="edit-full-name" name="full_name" type="text" maxlength="150" autocomplete="name" required data-edit-full-name>
                    <label for="edit-email">Correo</label>
                    <input id="edit-email" name="email" type="email" maxlength="190" autocomplete="email" required data-edit-email>
                    <label for="edit-role">Perfil</label>
                    <select id="edit-role" name="role" data-user-role required data-edit-role>
                        <option value="talento_humano">Talento Humano</option>
                        <option value="admin">Administrador</option>
                        <option value="instructor">Analista</option>
                    </select>
                    <div data-instructor-field hidden>
                        <label for="edit-instructor-id">Instructor vinculado</label>
                        <select id="edit-instructor-id" name="instructor_id" data-instructor-select disabled>
                            <option value="">Selecciona un instructor</option>
                            <?php foreach ($instructors as $instructor): ?>
                                <?php $instructorOption = trim($instructor['first_name'] . ' ' . $instructor['last_name']); ?>
                                <option value="<?= e($instructor['id']) ?>" data-linked-user-id="<?= e($instructor['linked_user_id'] ?? '') ?>" <?= $instructor['linked_user_id'] !== null ? 'disabled' : '' ?>>
                                    <?= e($instructorOption . ($instructor['document_number'] ? ' · ' . $instructor['document_number'] : '')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <label for="edit-password">Nueva contraseña (opcional, mínimo 12 caracteres)</label>
                    <input id="edit-password" name="password" type="password" minlength="12" autocomplete="new-password">
                    <div class="dialog-actions">
                        <button class="button button-secondary" type="button" data-close-dialog>Cancelar</button>
                        <button class="button button-primary" type="submit">Guardar cambios</button>
                    </div>
                </form>
            </dialog>
<?php require __DIR__ . '/includes/manager_footer.php'; ?>
