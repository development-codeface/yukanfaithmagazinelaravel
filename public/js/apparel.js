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
    document.querySelectorAll('[name="variant_id"]').forEach(input => input.addEventListener('change', () => {
        document.getElementById('product-price').textContent = '$' + Number(input.dataset.price).toFixed(2);
        quantity.max = Math.min(99, Number(input.dataset.stock));
        quantity.value = Math.max(1, Math.min(Number(quantity.value) || 1, Number(quantity.max)));
        showImage(input.dataset.image);
    }));
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
