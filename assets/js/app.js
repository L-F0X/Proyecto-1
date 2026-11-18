document.querySelectorAll('[data-open-dialog]').forEach((button) => {
    button.addEventListener('click', () => {
        const dialog = document.getElementById(button.dataset.openDialog);
        if (dialog instanceof HTMLDialogElement) {
            dialog.showModal();
        }
    });
});

document.querySelectorAll('[data-close-dialog]').forEach((button) => {
    button.addEventListener('click', () => button.closest('dialog')?.close());
});

const sidebar = document.querySelector('.sidebar');
const sidebarToggle = sidebar?.querySelector('[data-sidebar-toggle]');
const sidebarNavigation = sidebar?.querySelector('#sidebar-navigation');

if (sidebar instanceof HTMLElement && sidebarToggle instanceof HTMLButtonElement && sidebarNavigation instanceof HTMLElement) {
    const setSidebarOpen = (isOpen) => {
        sidebar.classList.toggle('mobile-open', isOpen);
        sidebarToggle.setAttribute('aria-expanded', String(isOpen));
        sidebarToggle.setAttribute('aria-label', isOpen ? 'Cerrar menú de navegación' : 'Abrir menú de navegación');
    };

    sidebarToggle.addEventListener('click', () => {
        setSidebarOpen(!sidebar.classList.contains('mobile-open'));
    });

    sidebarNavigation.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => setSidebarOpen(false));
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            setSidebarOpen(false);
        }
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth > 760) {
            setSidebarOpen(false);
        }
    });
}

document.querySelectorAll('dialog').forEach((dialog) => {
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            dialog.close();
        }
    });
});

const updateInstructorField = (form) => {
    const roleSelect = form.querySelector('[data-user-role]');
    const field = form.querySelector('[data-instructor-field]');
    const instructorSelect = form.querySelector('[data-instructor-select]');
    if (!(roleSelect instanceof HTMLSelectElement) || !(field instanceof HTMLElement) || !(instructorSelect instanceof HTMLSelectElement)) {
        return;
    }

    const isInstructor = roleSelect.value === 'instructor';
    field.hidden = !isInstructor;
    instructorSelect.disabled = !isInstructor;
    instructorSelect.required = isInstructor;
    if (!isInstructor) {
        instructorSelect.value = '';
    }
};

document.querySelectorAll('.dialog-form').forEach((form) => {
    const roleSelect = form.querySelector('[data-user-role]');
    if (roleSelect instanceof HTMLSelectElement) {
        updateInstructorField(form);
        roleSelect.addEventListener('change', () => updateInstructorField(form));
    }
});

document.querySelectorAll('[data-edit-user]').forEach((button) => {
    button.addEventListener('click', () => {
        const dialog = document.getElementById('edit-user-dialog');
        const form = dialog?.querySelector('form');
        if (!(dialog instanceof HTMLDialogElement) || !(form instanceof HTMLFormElement)) {
            console.error('No se encontró el formulario para editar usuarios.');
            return;
        }

        const userId = button.dataset.userId ?? '';
        form.querySelector('[data-edit-user-id]').value = userId;
        form.querySelector('[data-edit-full-name]').value = button.dataset.fullName ?? '';
        form.querySelector('[data-edit-email]').value = button.dataset.email ?? '';
        form.querySelector('[data-edit-role]').value = button.dataset.role ?? 'talento_humano';
        form.querySelector('#edit-password').value = '';

        const instructorSelect = form.querySelector('[data-instructor-select]');
        if (instructorSelect instanceof HTMLSelectElement) {
            Array.from(instructorSelect.options).forEach((option) => {
                const linkedUserId = option.dataset.linkedUserId ?? '';
                option.disabled = linkedUserId !== '' && linkedUserId !== userId;
            });
            instructorSelect.value = button.dataset.instructorId ?? '';
        }

        updateInstructorField(form);
        dialog.showModal();
    });
});

document.querySelectorAll('[data-edit-staff]').forEach((button) => {
    button.addEventListener('click', () => {
        const dialog = document.getElementById('edit-staff-dialog');
        const form = dialog?.querySelector('form');
        if (!(dialog instanceof HTMLDialogElement) || !(form instanceof HTMLFormElement)) {
            console.error('No se encontró el formulario para editar el registro.');
            return;
        }

        form.querySelector('[data-edit-staff-id]').value = button.dataset.staffId ?? '';
        form.querySelectorAll('[data-staff-field]').forEach((field) => {
            const dataKey = field.dataset.staffField.replace(/_([a-z])/g, (_, letter) => letter.toUpperCase());
            field.value = button.dataset[dataKey] ?? '';
            if (field instanceof HTMLInputElement && field.dataset.staffField === 'job_title') {
                field.readOnly = ['administrativo', 'instructor'].includes(field.value.trim().toLocaleLowerCase());
            }
        });
        dialog.showModal();
    });
});

document.querySelectorAll('[data-confirm-user-delete]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (!window.confirm('¿Eliminar esta cuenta de usuario? Esta acción no se puede deshacer.')) {
            event.preventDefault();
        }
    });
});

document.querySelectorAll('[data-confirm-staff-delete]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (!window.confirm('¿Eliminar este registro del directorio? Esta acción no se puede deshacer.')) {
            event.preventDefault();
        }
    });
});

const tableFilterGroups = new Map();
document.querySelectorAll('[data-table-filter]').forEach((input) => {
    const tableId = input.dataset.tableFilter;
    const inputs = tableFilterGroups.get(tableId) ?? [];
    inputs.push(input);
    tableFilterGroups.set(tableId, inputs);
});

tableFilterGroups.forEach((inputs, tableId) => {
    const table = document.getElementById(tableId);
    const searchInput = inputs.find((input) => input.type === 'search');
    const searchableColumns = searchInput?.dataset.searchColumns === undefined
        ? null
        : searchInput.dataset.searchColumns
            .split(',')
            .map((index) => Number(index.trim()))
            .filter((index) => Number.isInteger(index) && index >= 0);

    const applyFilters = () => {
        table?.querySelectorAll('tbody tr').forEach((row) => {
            const query = searchInput?.value.trim().toLocaleLowerCase() ?? '';
            const searchableText = searchableColumns === null
                ? row.textContent
                : searchableColumns.map((index) => row.cells.item(index)?.textContent ?? '').join(' ');
            const matchesSearch = searchableText.toLocaleLowerCase().includes(query);

            row.hidden = !matchesSearch;
        });
    };

    inputs.forEach((input) => {
        input.addEventListener('input', applyFilters);
    });
});

document.querySelectorAll('[data-export-table]').forEach((button) => {
    button.addEventListener('click', () => {
        const table = document.getElementById(button.dataset.exportTable);
        if (!(table instanceof HTMLTableElement)) {
            console.error('No se encontró la tabla para exportar.');
            return;
        }

        const csvCell = (value) => {
            let text = value.replace(/\s+/g, ' ').trim();
            if (/^[=+\-@]/.test(text)) {
                text = `'${text}`;
            }
            return `"${text.replace(/"/g, '""')}"`;
        };
        const isExportable = (cell) => !cell.classList.contains('no-export');
        const headers = Array.from(table.querySelectorAll('thead th'))
            .filter(isExportable)
            .map((cell) => csvCell(cell.textContent));
        const rows = Array.from(table.querySelectorAll('tbody tr'))
            .filter((row) => !row.hidden && !row.querySelector('.empty-state'))
            .map((row) => Array.from(row.cells)
                .filter(isExportable)
                .map((cell) => csvCell(cell.textContent)));
        const csv = `\uFEFF${[headers, ...rows].map((row) => row.join(',')).join('\r\n')}`;
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8' });
        const url = URL.createObjectURL(blob);
        const download = document.createElement('a');
        download.href = url;
        download.download = `${button.dataset.exportFilename}-${new Date().toISOString().slice(0, 10)}.csv`;
        document.body.append(download);
        download.click();
        download.remove();
        setTimeout(() => URL.revokeObjectURL(url), 1000);
    });
});
