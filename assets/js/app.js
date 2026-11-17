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

document.querySelectorAll('dialog').forEach((dialog) => {
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            dialog.close();
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
    const fromInput = inputs.find((input) => input.dataset.dateBound === 'from');
    const toInput = inputs.find((input) => input.dataset.dateBound === 'to');
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
            const dateCell = row.querySelector('[data-filter-date]');
            const rowDate = dateCell?.dataset.filterDate ?? '';
            const fromDate = fromInput?.value ?? '';
            const toDate = toInput?.value ?? '';
            const matchesSearch = searchableText.toLocaleLowerCase().includes(query);
            const matchesDate = fromDate === '' && toDate === ''
                ? true
                : rowDate !== '' && (fromDate === '' || rowDate >= fromDate) && (toDate === '' || rowDate <= toDate);

            row.hidden = !matchesSearch || !matchesDate;
        });
    };

    inputs.forEach((input) => {
        input.addEventListener(input.type === 'date' ? 'change' : 'input', applyFilters);
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
        const headers = Array.from(table.querySelectorAll('thead th'), (cell) => csvCell(cell.textContent));
        const rows = Array.from(table.querySelectorAll('tbody tr'))
            .filter((row) => !row.hidden && !row.querySelector('.empty-state'))
            .map((row) => Array.from(row.cells, (cell) => csvCell(cell.textContent)));
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
