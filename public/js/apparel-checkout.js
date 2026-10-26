(() => {
    const form = document.getElementById('checkout-wizard');
    if (!form) return;
    const sections = [...form.querySelectorAll('[data-checkout-step]')];
    const error = document.getElementById('checkout-error');
    let step = 0;
    let authenticated = form.dataset.authenticated === 'true';
    const value = name => form.elements.namedItem(name).value.trim();
    const showStep = next => {
        step = next;
        sections.forEach((section, index) => { section.hidden = index !== step; });
        document.querySelectorAll('[data-progress]').forEach((item, index) => {
            item.classList.toggle('is-complete', index < step);
            if (index === step) item.setAttribute('aria-current', 'step');
            else item.removeAttribute('aria-current');
            item.querySelector('span').textContent = index < step ? '\u2713' : index + 1;
        });
        error.hidden = true;
        if (step === 3) {
            form.querySelector('[data-review-contact]').textContent = `${value('first_name')} ${value('last_name')}\n${value('email')}\n${value('phone')}`;
            form.querySelector('[data-review-address]').textContent = [value('street'), value('street2'), `${value('city')}, ${value('region')} ${value('postal_code')}`, value('country')].filter(Boolean).join('\n');
        }
        const heading = sections[step].querySelector('h1');
        heading.tabIndex = -1;
        heading.focus({preventScroll: true});
        window.scrollTo({top: 0, behavior: 'smooth'});
    };
    form.addEventListener('click', async event => {
        const next = event.target.closest('[data-checkout-next]');
        if (event.target.closest('[data-checkout-back]')) { showStep(Math.max(0, step - 1)); return; }
        if (!next) return;
        const inputs = [...sections[step].querySelectorAll('input, textarea, select')].filter(input => !input.disabled);
        if (inputs.some(input => !input.reportValidity())) return;
        if (step === 0 && !authenticated) {
            next.disabled = true;
            error.hidden = true;
            try {
                const response = await fetch(form.dataset.accountUrl, {method: 'POST', body: new FormData(form), headers: {'Accept': 'application/json'}});
                const data = await response.json();
                if (!response.ok) {
                    error.textContent = Object.values(data.errors || {}).flat()[0] || 'Unable to continue. Please refresh and try again.';
                    error.hidden = false;
                    return;
                }
                authenticated = true;
                document.querySelectorAll('input[name="_token"]').forEach(input => { input.value = data.csrf_token; });
                document.getElementById('checkout-account-fields').hidden = true;
                ['password', 'password_confirmation'].forEach(name => { form.elements.namedItem(name).disabled = true; });
            } catch (_) {
                error.textContent = 'Unable to connect. Please try again.';
                error.hidden = false;
                return;
            } finally { next.disabled = false; }
        }
        showStep(Math.min(3, step + 1));
    });
    form.addEventListener('submit', event => {
        if (step !== 3) { event.preventDefault(); sections[step].querySelector('[data-checkout-next]')?.click(); return; }
        form.querySelector('button[type="submit"]').disabled = true;
    });
    if (authenticated) ['password', 'password_confirmation'].forEach(name => { form.elements.namedItem(name).disabled = true; });
})();
