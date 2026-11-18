<?php
$staffFields = [
    ['name' => 'document_number', 'label' => 'Documento de identidad', 'type' => 'text', 'max' => 50],
    ['name' => 'first_name', 'label' => 'Nombres', 'type' => 'text', 'max' => 100, 'required' => true],
    ['name' => 'last_name', 'label' => 'Apellidos', 'type' => 'text', 'max' => 100, 'required' => true],
    ['name' => 'email', 'label' => 'Correo electrónico institucional', 'type' => 'email', 'max' => 190],
    ['name' => 'phone', 'label' => 'Número celular', 'type' => 'text', 'max' => 40],
    ['name' => 'personal_email', 'label' => 'Correo personal', 'type' => 'email', 'max' => 190],
    ['name' => 'affiliation_type', 'label' => 'Tipo de vinculación', 'type' => 'select'],
    ['name' => 'job_title', 'label' => 'Cargo', 'type' => 'text', 'max' => 150],
    [
        'name' => $staffCategory === 'administrative' ? 'dependency' : 'area',
        'label' => $staffCategory === 'administrative' ? 'Dependencia' : 'Area',
        'type' => 'text',
        'max' => 50,
    ],
];
$staffCargoDefault = $staffCategory === 'administrative' ? 'Administrativo' : 'Instructor';
if ($staffCategory === 'administrative') {
    $staffFields[] = ['name' => 'supervisor', 'label' => 'Supervisor o jefe inmediato', 'type' => 'text', 'max' => 150];
    $staffFields[] = ['name' => 'contract_number', 'label' => 'Número de contrato', 'type' => 'text', 'max' => 80];
}
$staffFields[] = ['name' => 'contract_start_date', 'label' => 'Fecha de inicio', 'type' => 'date'];
$staffFields[] = ['name' => 'contract_end_date', 'label' => 'Fecha de terminación', 'type' => 'date'];
?>
<dialog class="form-dialog staff-form-dialog" id="create-staff-dialog">
    <form class="dialog-form" action="staff_actions.php" method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="create">
        <input type="hidden" name="category" value="<?= e($staffCategory) ?>">
        <div class="dialog-heading">
            <div><p class="eyebrow">NUEVO REGISTRO</p><h2>Añadir <?= e($staffFormTitle) ?></h2></div>
            <button class="dialog-close" type="button" data-close-dialog aria-label="Cerrar">×</button>
        </div>
        <div class="staff-form-grid">
            <?php foreach ($staffFields as $field): ?>
                <?php $fieldId = 'create-' . $staffCategory . '-' . $field['name']; ?>
                <div class="staff-form-field">
                    <label for="<?= e($fieldId) ?>"><?= e($field['label']) ?></label>
                    <?php if ($field['type'] === 'select'): ?>
                        <select id="<?= e($fieldId) ?>" name="<?= e($field['name']) ?>" data-staff-field="<?= e($field['name']) ?>">
                            <option value="">Sin especificar</option>
                            <?php foreach ($affiliationOptions as $affiliationOption): ?>
                                <option value="<?= e($affiliationOption) ?>"><?= e($affiliationOption) ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <input id="<?= e($fieldId) ?>" name="<?= e($field['name']) ?>" type="<?= e($field['type']) ?>"
                            <?= isset($field['max']) ? 'maxlength="' . e($field['max']) . '"' : '' ?>
                            <?= !empty($field['required']) ? 'required' : '' ?>
                            <?= $field['name'] === 'job_title' ? 'readonly' : '' ?>
                            <?= $field['name'] === 'job_title' ? 'value="' . e($staffCargoDefault) . '"' : '' ?>
                            data-staff-field="<?= e($field['name']) ?>">
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="dialog-actions">
            <button class="button button-secondary" type="button" data-close-dialog>Cancelar</button>
            <button class="button button-primary" type="submit">Guardar registro</button>
        </div>
    </form>
</dialog>

<dialog class="form-dialog staff-form-dialog" id="edit-staff-dialog">
    <form class="dialog-form" action="staff_actions.php" method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="category" value="<?= e($staffCategory) ?>">
        <input type="hidden" name="staff_id" data-edit-staff-id>
        <div class="dialog-heading">
            <div><p class="eyebrow">ACTUALIZAR REGISTRO</p><h2>Editar <?= e($staffFormTitle) ?></h2></div>
            <button class="dialog-close" type="button" data-close-dialog aria-label="Cerrar">×</button>
        </div>
        <div class="staff-form-grid">
            <?php foreach ($staffFields as $field): ?>
                <?php $fieldId = 'edit-' . $staffCategory . '-' . $field['name']; ?>
                <div class="staff-form-field">
                    <label for="<?= e($fieldId) ?>"><?= e($field['label']) ?></label>
                    <?php if ($field['type'] === 'select'): ?>
                        <select id="<?= e($fieldId) ?>" name="<?= e($field['name']) ?>" data-staff-field="<?= e($field['name']) ?>">
                            <option value="">Sin especificar</option>
                            <?php foreach ($affiliationOptions as $affiliationOption): ?>
                                <option value="<?= e($affiliationOption) ?>"><?= e($affiliationOption) ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <input id="<?= e($fieldId) ?>" name="<?= e($field['name']) ?>" type="<?= e($field['type']) ?>"
                            <?= isset($field['max']) ? 'maxlength="' . e($field['max']) . '"' : '' ?>
                            <?= !empty($field['required']) ? 'required' : '' ?>
                            data-staff-field="<?= e($field['name']) ?>">
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="dialog-actions">
            <button class="button button-secondary" type="button" data-close-dialog>Cancelar</button>
            <button class="button button-primary" type="submit">Guardar cambios</button>
        </div>
    </form>
</dialog>
