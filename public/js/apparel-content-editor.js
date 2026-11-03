(() => {
    const editor = document.getElementById('product-content-editor');
    if (!editor) return;
    const splitValues = value => value.split(',').map(part => part.trim());
    function preview() {
        const target = editor.querySelector('[data-chart-preview]');
        const columns = splitValues(editor.querySelector('[name="size_chart_columns"]').value);
        const rows = [...editor.querySelector('[data-content-list="measurements"]').children];
        target.replaceChildren();
        if (!rows.length) {
            target.textContent = 'Add a measurement to preview the table.';
            return;
        }
        const cell = (tag, value) => {
            const element = document.createElement(tag);
            element.textContent = value;
            if (tag === 'th') element.scope = 'col';
            return element;
        };
        for (const [unit, title] of [['imperial', 'Inches'], ['metric', 'Centimeters']]) {
            const scroll = document.createElement('div');
            scroll.className = 'chart-preview-scroll';
            const table = document.createElement('table');
            table.className = 'chart-preview-table';
            const caption = document.createElement('caption');
            caption.textContent = title;
            table.append(caption);
            const header = table.createTHead().insertRow();
            header.append(cell('th', 'Attribute'), ...columns.map(column => cell('th', column || 'Enter size')));
            const body = table.createTBody();
            for (const row of rows) {
                const values = splitValues(row.querySelector(`[name$="[${unit}]"]`).value);
                const tr = body.insertRow();
                const heading = cell('th', row.querySelector('[name$="[label]"]').value || 'Enter attribute');
                heading.scope = 'row';
                tr.append(heading, ...columns.map((_, index) => cell('td', values[index] || '—')));
                if (values.length !== columns.length || values.some(value => !value)) {
                    const warning = document.createElement('p');
                    warning.className = 'text-danger';
                    warning.textContent = `${heading.textContent}: enter ${columns.length} comma-separated ${title.toLowerCase()} value(s), one per column.`;
                    target.append(warning);
                }
            }
            scroll.append(table);
            target.append(scroll);
        }
    }
    function renumber(list) {
        [...list.children].forEach((row, index) => row.querySelectorAll('[name]').forEach(input => {
            input.name = input.name.replace(/\[\d+\]/, `[${index}]`);
        }));
    }
    function iconPreview(input) {
        const target = input.closest('[data-content-row]').querySelector('[data-detail-icon-preview]');
        const file = input.files[0];
        target.replaceChildren();
        const showImage = source => {
            const image = document.createElement('img');
            image.src = source;
            image.alt = file ? 'Selected icon (save to upload)' : 'Current detail icon';
            target.append(image);
        };
        if (!file) {
            if (target.dataset.savedIcon) showImage(target.dataset.savedIcon);
            return;
        }
        const reader = new FileReader();
        reader.addEventListener('load', () => {
            if (input.files[0] !== file) return;
            showImage(reader.result);
            const clear = document.createElement('button');
            clear.type = 'button';
            clear.className = 'btn btn-sm ml-2';
            clear.textContent = 'Clear selected image';
            clear.addEventListener('click', () => { input.value = ''; iconPreview(input); });
            target.append(clear);
        });
        reader.readAsDataURL(file);
    }
    editor.addEventListener('change', event => {
        if (event.target.matches('[data-detail-icon-upload]')) iconPreview(event.target);
    });
    editor.addEventListener('click', event => {
        const add = event.target.closest('[data-add-content]');
        if (add) {
            const list = editor.querySelector(`[data-content-list="${add.dataset.addContent}"]`);
            const template = document.getElementById(add.dataset.addContent === 'details' ? 'detail-template' : 'measurement-template');
            list.append(template.content.cloneNode(true));
            renumber(list);
            list.lastElementChild.querySelector('input,select')?.focus();
            preview();
        }
        const action = event.target.closest('[data-row-action]');
        if (!action) return;
        const row = action.closest('[data-content-row]'), list = row.parentElement;
        if (action.dataset.rowAction === 'remove') row.remove();
        renumber(list);
        preview();
    });
    editor.addEventListener('input', preview);
    preview();
})();
