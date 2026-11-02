(() => {
    const form = document.getElementById('checkout-wizard');
    if (!form) return;
    const error = document.getElementById('checkout-error');
    const status = document.getElementById('checkout-status');
    const submit = document.getElementById('checkout-submit');
    const confirmation = form.elements.namedItem('password_confirmation');
    const password = form.elements.namedItem('password');
    let authenticated = form.dataset.authenticated === 'true';
    let busy = false;
    const accountMode = () => form.querySelector('[name="account_mode"]:checked')?.value || 'register';
    const updateAccount = () => {
        const register = accountMode() === 'register';
        password.disabled = authenticated;
        password.required = !authenticated;
        password.autocomplete = register ? 'new-password' : 'current-password';
        confirmation.disabled = authenticated || !register;
        confirmation.required = !authenticated && register;
        document.getElementById('checkout-register-fields').hidden = authenticated || !register;
        document.getElementById('account-help').textContent = register
            ? 'Create an account so you can track your order.'
            : 'Sign in with your existing account. Your delivery details will stay here.';
    };
    form.querySelectorAll('[name="account_mode"]').forEach(input => input.addEventListener('change', updateAccount));
    updateAccount();
    const post = async url => {
        const response = await fetch(url, {method: 'POST', body: new FormData(form), headers: {'Accept': 'application/json'}});
        if (response.status === 419) throw new Error('Your session expired. Refresh this page and try again.');
        const data = await response.json();
        if (!response.ok) {
            if (data.redirect) return data;
            const field = Object.keys(data.errors || {})[0];
            const input = field && form.elements.namedItem(field);
            input?.focus();
            throw new Error(Object.values(data.errors || {}).flat()[0] || data.message || 'Unable to continue. Please try again.');
        }
        return data;
    };
    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (busy || !form.reportValidity()) return;
        busy = true;
        submit.disabled = true;
        submit.textContent = 'Opening secure payment…';
        error.hidden = true;
        let navigating = false;
        try {
            if (!authenticated) {
                status.textContent = accountMode() === 'login' ? 'Signing you in…' : 'Creating your account…';
                const account = await post(form.dataset.accountUrl);
                if (!account) return;
                authenticated = true;
                document.querySelectorAll('input[name="_token"]').forEach(input => { input.value = account.csrf_token; });
                document.getElementById('checkout-account-fields').hidden = true;
                form.querySelector('.checkout-account-choice').hidden = true;
                updateAccount();
            }
            status.textContent = 'Saving your details and opening payment…';
            const order = await post(form.action);
            if (order?.redirect) {
                navigating = true;
                window.location.assign(order.redirect);
            }
        } catch (e) {
            error.textContent = e.message || 'Unable to connect. Your details are still here; please try again.';
            error.hidden = false;
            error.scrollIntoView({behavior: 'smooth', block: 'center'});
        } finally {
            if (!navigating) {
                busy = false;
                submit.disabled = false;
                submit.textContent = 'Continue to secure payment';
                status.textContent = '';
            }
        }
    });
})();
