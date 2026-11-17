(() => {
    const form = document.getElementById('product-filters');
    const results = document.getElementById('product-results');
    const status = document.getElementById('product-search-status');
    if (!form || !results) return;
    let timer;
    let controller;
    let generation = 0;

    const filterUrl = () => {
        const url = new URL(window.location.href);
        url.search = new URLSearchParams(new FormData(form)).toString();
        return url;
    };
    const load = async (url, updateHistory = true) => {
        clearTimeout(timer);
        controller?.abort();
        controller = new AbortController();
        const requestGeneration = ++generation;
        results.setAttribute('aria-busy', 'true');
        status.textContent = 'Loading products…';
        try {
            const response = await fetch(url, {
                headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'},
                signal: controller.signal,
            });
            if (!response.ok) throw new Error('Search failed');
            const data = await response.json();
            if (requestGeneration !== generation) return;
            results.innerHTML = data.html;
            if (updateHistory) history.replaceState(null, '', url);
            status.textContent = results.querySelector('.empty-state') ? 'No matching products.' : 'Products updated.';
        } catch (error) {
            if (error.name !== 'AbortError' && requestGeneration === generation) {
                status.textContent = 'Unable to load products. Please try again.';
            }
        } finally {
            if (requestGeneration === generation) results.removeAttribute('aria-busy');
        }
    };
    form.addEventListener('submit', event => { event.preventDefault(); load(filterUrl()); });
    form.querySelector('[name="search"]').addEventListener('input', () => {
        clearTimeout(timer);
        controller?.abort();
        generation++;
        timer = setTimeout(() => load(filterUrl()), 300);
    });
    form.querySelector('[name="sort"]').addEventListener('change', () => load(filterUrl()));
    results.addEventListener('click', event => {
        const link = event.target.closest('.pagination a');
        if (!link || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button !== 0) return;
        event.preventDefault();
        load(new URL(link.href));
    });
    window.addEventListener('popstate', () => {
        const url = new URL(location.href);
        form.elements.search.value = url.searchParams.get('search') || '';
        form.elements.sort.value = url.searchParams.get('sort') || 'recommended';
        load(url, false);
    });
})();
