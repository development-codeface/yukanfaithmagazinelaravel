(() => {
    const form = document.querySelector('.apparel-product-form');
    if (!form) return;
    const tabs = [...form.querySelectorAll('[data-product-tab]')];
    const panels = [...form.querySelectorAll('[data-product-panel]')];
    function selectTab(name, focus = false) {
        tabs.forEach(tab => {
            const active = tab.dataset.productTab === name;
            tab.setAttribute('aria-selected', String(active));
            tab.tabIndex = active ? 0 : -1;
            if (active && focus) tab.focus();
        });
        panels.forEach(panel => { panel.hidden = panel.dataset.productPanel !== name; });
    }
    form.querySelector('.product-tabs').hidden = false;
    selectTab('basics');
    tabs.forEach((tab, index) => {
        tab.addEventListener('click', () => selectTab(tab.dataset.productTab));
        tab.addEventListener('keydown', event => {
            let next;
            if (event.key === 'ArrowRight') next = (index + 1) % tabs.length;
            if (event.key === 'ArrowLeft') next = (index + tabs.length - 1) % tabs.length;
            if (event.key === 'Home') next = 0;
            if (event.key === 'End') next = tabs.length - 1;
            if (next === undefined) return;
            event.preventDefault();
            selectTab(tabs[next].dataset.productTab, true);
        });
    });
    // Reveal required inputs before the browser attempts to focus invalid fields.
    let invalidTabRevealed = false;
    form.addEventListener('invalid', event => {
        const panel = event.target.closest('[data-product-panel]');
        if (!panel) return;
        tabs.find(tab => tab.dataset.productTab === panel.dataset.productPanel)?.classList.add('has-error');
        if (!invalidTabRevealed) {
            selectTab(panel.dataset.productPanel);
            invalidTabRevealed = true;
            setTimeout(() => { invalidTabRevealed = false; }, 0);
        }
    }, true);
    const errorKeys = JSON.parse(form.querySelector('[data-product-errors]').textContent);
    let firstErrorPanel;
    for (const key of errorKeys) {
        const name = key.replace(/\.([^.]*)/g, '[$1]');
        const field = [...form.elements].find(input => input.name === name || input.name?.startsWith(name + '['));
        const panel = field?.closest('[data-product-panel]') || panels.find(item =>
            item.dataset.productPanel === (/^(options|variants|remove_variant_images)/.test(key) ? 'variants'
                : /^(size_chart|detail_sections)/.test(key) ? 'content' : /^images|^remove_images/.test(key) ? 'images' : 'basics'));
        if (panel) {
            firstErrorPanel ||= panel;
            tabs.find(tab => tab.dataset.productTab === panel.dataset.productPanel)?.classList.add('has-error');
        }
    }
    if (firstErrorPanel) selectTab(firstErrorPanel.dataset.productPanel);

    const uploads = new Map();
    function renderUpload(input, state) {
        state.urls.forEach(url => URL.revokeObjectURL(url));
        state.urls = [];
        state.preview.replaceChildren();
        const transfer = new DataTransfer();
        state.files.forEach(file => transfer.items.add(file));
        input.files = transfer.files;
        state.files.forEach((file, index) => {
            const card = document.createElement('div');
            card.className = 'pending-image-card';
            const image = document.createElement('img');
            image.alt = file.name;
            image.src = URL.createObjectURL(file);
            state.urls.push(image.src);
            const caption = document.createElement('span');
            caption.textContent = file.name;
            caption.title = file.name;
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.textContent = 'Remove';
            remove.setAttribute('aria-label', `Remove selected image ${file.name}`);
            remove.addEventListener('click', () => {
                state.files.splice(index, 1);
                renderUpload(input, state);
            });
            card.append(image, caption, remove);
            state.preview.append(card);
        });
        state.help.textContent = state.files.length
            ? `${state.files.length} image(s) selected — not uploaded yet. Choose files again to add more, then Save Product.`
            : 'Select multiple images using Ctrl or Shift, or choose files again to add more. Save Product to upload.';
    }
    function initializeUploads() {
        form.querySelectorAll('input[type="file"][multiple]').forEach(input => {
            if (uploads.has(input)) return;
            const help = document.createElement('span');
            help.className = 'upload-help';
            const preview = document.createElement('div');
            preview.className = 'pending-image-list';
            preview.setAttribute('aria-label', 'Selected image previews');
            const context = input.closest('tr')?.querySelector('strong')?.textContent || 'Product gallery';
            input.setAttribute('aria-label', `${context} images`);
            input.after(help, preview);
            const state = {files: [], urls: [], help, preview};
            uploads.set(input, state);
            renderUpload(input, state);
        });
        for (const [input, state] of uploads) {
            if (!form.contains(input)) {
                state.urls.forEach(url => URL.revokeObjectURL(url));
                uploads.delete(input);
            }
        }
        form.querySelectorAll('#variants-table tbody tr').forEach(row => {
            const combination = row.querySelector('strong')?.textContent || 'Variant';
            const labels = ['Combination', 'SKU', 'Price override', 'Stock', 'Images', 'Active', 'Exclude'];
            [...row.cells].forEach((cell, index) => {
                cell.querySelectorAll('input:not([type="hidden"]):not([type="file"])').forEach(input => {
                    input.setAttribute('aria-label', `${combination}: ${labels[index]}`);
                });
            });
        });
    }
    form.addEventListener('change', event => {
        const input = event.target;
        const state = uploads.get(input);
        if (!state) return;
        const added = [...input.files];
        for (const file of added) {
            if (!state.files.some(previous => previous.name === file.name && previous.size === file.size && previous.lastModified === file.lastModified)) {
                state.files.push(file);
            }
        }
        renderUpload(input, state);
    });
    initializeUploads();
    new MutationObserver(initializeUploads).observe(form.querySelector('#variants-table tbody'), {childList: true});
    window.addEventListener('pagehide', () => {
        for (const state of uploads.values()) state.urls.forEach(url => URL.revokeObjectURL(url));
    });
})();
