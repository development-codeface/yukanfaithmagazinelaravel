(() => {
    const main = document.getElementById('main-image');
    const thumbnails = document.querySelectorAll('.thumbnail');
    const showImage = (url, alt) => {
        if (!main || !url) return;
        main.src = url;
        if (alt) main.alt = alt;
        thumbnails.forEach(button => button.setAttribute('aria-pressed', String(button.dataset.image === url)));
    };
    thumbnails.forEach(button => button.addEventListener('click', () => showImage(button.dataset.image, button.dataset.alt)));
    const quantity = document.getElementById('quantity');
    document.querySelectorAll('[data-step]').forEach(button => button.addEventListener('click', () => {
        const current = Number(quantity.value) || 1;
        quantity.value = Math.min(Number(quantity.max), Math.max(1, current + Number(button.dataset.step)));
    }));
    const radios = [...document.querySelectorAll('[name="variant_id"]')];
    radios.forEach(input => input.addEventListener('change', () => {
        const currency = document.querySelector('meta[name="apparel-currency"]')?.content || 'INR';
        document.getElementById('product-price').textContent = (currency === 'USD' ? '$' : currency + ' ') + Number(input.dataset.price).toFixed(2);
        quantity.max = Math.min(99, Number(input.dataset.stock));
        quantity.value = Math.max(1, Math.min(Number(quantity.value) || 1, Number(quantity.max)));
        showImage(input.dataset.image);
    }));
    const payload = document.getElementById('product-variants');
    const selectors = document.getElementById('variant-selectors');
    if (payload && selectors) {
        const variants = JSON.parse(payload.textContent);
        const groups = new Map();
        variants.forEach(variant => variant.options.forEach(option => {
            if (!groups.has(option.key)) groups.set(option.key, {...option, values: new Map()});
            groups.get(option.key).values.set(option.value, option);
        }));
        const ordered = [...groups.values()].sort((a, b) => (a.type === 'color' ? 0 : 1) - (b.type === 'color' ? 0 : 1));
        const optionValue = (variant, key) => variant.options.find(option => option.key === key)?.value;
        let selected;
        const controls = [];
        ordered.forEach(group => {
            const fieldset = document.createElement('fieldset');
            fieldset.className = 'swatch-options';
            const legend = document.createElement('legend');
            fieldset.append(legend);
            const row = document.createElement('div');
            row.className = 'swatch-row';
            fieldset.append(row);
            group.values.forEach(option => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = group.type === 'color' && option.hex ? 'color-swatch' : 'size-option';
                button.title = option.value;
                button.setAttribute('aria-label', option.value);
                if (group.type === 'color' && option.hex) button.style.setProperty('--swatch', option.hex);
                else button.textContent = option.value;
                button.addEventListener('click', () => {
                    const candidates = variants.filter(variant => variant.stock > 0 && optionValue(variant, group.key) === option.value);
                    const score = variant => ordered.reduce((total, other) => total + (other.key !== group.key && optionValue(variant, other.key) === optionValue(selected, other.key) ? 1 : 0), 0);
                    candidates.sort((a, b) => score(b) - score(a));
                    if (candidates[0]) select(candidates[0]);
                });
                row.append(button);
                controls.push({button, group, option, legend});
            });
            selectors.append(fieldset);
        });
        const select = variant => {
            selected = variant;
            const radio = radios.find(input => Number(input.value) === variant.id);
            if (radio) { radio.checked = true; radio.dispatchEvent(new Event('change')); }
            controls.forEach(({button, group, option, legend}) => {
                legend.textContent = `${group.name}: ${optionValue(variant, group.key) || 'Choose an option'}`;
                button.setAttribute('aria-pressed', String(optionValue(variant, group.key) === option.value));
                const index = ordered.indexOf(group);
                button.disabled = !variants.some(candidate => candidate.stock > 0 && optionValue(candidate, group.key) === option.value && ordered.slice(0, index).every(previous => optionValue(candidate, previous.key) === optionValue(variant, previous.key)));
            });
            const colorGroup = ordered.find(group => group.type === 'color');
            let images = variant.images;
            if (!images.length && colorGroup) images = variants.filter(candidate => optionValue(candidate, colorGroup.key) === optionValue(variant, colorGroup.key)).flatMap(candidate => candidate.images);
            const visible = [...thumbnails].filter(button => images.length ? images.includes(Number(button.dataset.imageId)) : !button.dataset.variantId);
            thumbnails.forEach(button => { button.hidden = !visible.includes(button); });
            if (visible[0]) showImage(visible[0].dataset.image, visible[0].dataset.alt);
            else if (main) { main.removeAttribute('src'); main.alt = 'Image coming soon'; }
            const add = document.querySelector('#purchase-form .add-button');
            add.disabled = variant.stock < 1;
            add.textContent = variant.stock < 1 ? 'Sold out' : 'Add to cart';
        };
        const requested = Number(new URLSearchParams(location.search).get('variant'));
        const initial = variants.find(variant => variant.id === requested) || variants.find(variant => variant.stock > 0) || variants[0];
        if (initial) {
            document.querySelector('.variant-options').hidden = true;
            select(initial);
        }
    }
})();

// Keep the shared cart over the page the customer is browsing.
(() => {
    const root = document.getElementById('cart-drawer-root');
    if (!root) return;
    let opener;
    let busy = false;
    const drawer = () => root.querySelector('#cart-drawer');
    const setOpen = (open) => {
        drawer().hidden = !open;
        document.body.classList.toggle('cart-is-open', open);
        document.querySelectorAll('[data-cart-open]').forEach(link => link.setAttribute('aria-expanded', String(open)));
        try { sessionStorage.setItem('apparel-cart-open', String(open)); } catch (_) {}
        if (open) drawer().querySelector('[data-cart-close]').focus();
        else if (opener?.isConnected) opener.focus();
    };
    const render = (data) => {
        root.innerHTML = data.html;
        document.querySelectorAll('[data-cart-count]').forEach(count => { count.textContent = data.count; });
        setOpen(true);
    };
    const showError = (message) => {
        setOpen(true);
        let notice = drawer().querySelector('[data-cart-error]');
        if (!notice) {
            notice = document.createElement('div');
            notice.className = 'notice error';
            notice.dataset.cartError = '';
            notice.setAttribute('role', 'alert');
            drawer().querySelector('.cart-panel-items').prepend(notice);
        }
        notice.textContent = message;
    };
    document.addEventListener('click', async event => {
        const open = event.target.closest('[data-cart-open]');
        if (open) {
            event.preventDefault();
            opener = open;
            setOpen(true);
            if (busy) return;
            try {
                const response = await fetch(root.dataset.cartUrl, {headers: {'Accept': 'application/json'}});
                if (!response.ok) throw new Error();
                render(await response.json());
            } catch (_) { showError('Unable to refresh your cart. Please try again.'); }
        }
        if (event.target.closest('[data-cart-close]')) {
            event.preventDefault();
            setOpen(false);
        }
    });
    document.addEventListener('submit', async event => {
        const form = event.target;
        if (!form.matches('#purchase-form, #cart-drawer form')) return;
        event.preventDefault();
        if (busy) return;
        busy = true;
        const data = new FormData(form);
        if (event.submitter?.name) data.append(event.submitter.name, event.submitter.value);
        const buttons = [...form.querySelectorAll('button')].map(button => [button, button.disabled]);
        buttons.forEach(([button]) => { button.disabled = true; });
        try {
            const response = await fetch(form.action, {method: 'POST', body: data, headers: {'Accept': 'application/json'}});
            const result = await response.json();
            if (!response.ok) {
                const message = Object.values(result.errors || {}).flat()[0] || 'Unable to update your cart. Please refresh and try again.';
                showError(message);
            } else {
                opener = form.id === 'purchase-form' ? event.submitter : opener;
                render(result);
            }
        } catch (_) { showError('Unable to update your cart. Please refresh and try again.'); }
        finally {
            busy = false;
            buttons.forEach(([button, disabled]) => { button.disabled = disabled; });
        }
    });
    document.addEventListener('keydown', event => {
        if (drawer().hidden) return;
        if (event.key === 'Escape') { setOpen(false); return; }
        if (event.key !== 'Tab') return;
        const focusable = [...drawer().querySelectorAll('a[href], button:not(:disabled), input:not([type="hidden"])')];
        const first = focusable[0], last = focusable[focusable.length - 1];
        if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
        else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
    });
    let persisted = false;
    try { persisted = sessionStorage.getItem('apparel-cart-open') === 'true'; } catch (_) {}
    const browsing = !/\/(checkout|orders)(\/|$)/.test(location.pathname);
    setOpen(root.dataset.initialOpen === 'true' || (persisted && browsing));
})();
