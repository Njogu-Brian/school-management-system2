(() => {
    const defaultUrl = '/students/search';
    const apiFallback = '/api/students/search';
    const debounce = (fn, ms = 600) => {
        let t;
        return (...args) => {
            clearTimeout(t);
            t = setTimeout(() => fn(...args), ms);
        };
    };

    const initOne = (wrapper) => {
        const hiddenId = wrapper.dataset.hidden;
        const displayId = wrapper.dataset.display;
        const resultsId = wrapper.dataset.results;
        const enableId = wrapper.dataset.enable;
        const customUrl = wrapper.dataset.searchUrl || '';

        const hidden = document.getElementById(hiddenId);
        const input = document.getElementById(displayId);
        const results = document.getElementById(resultsId);
        const enableBtn = enableId ? document.getElementById(enableId) : null;
        if (!hidden || !input || !results) return;

        const render = (items) => {
            results.innerHTML = '';
            if (!items.length) {
                results.classList.add('d-none');
                return;
            }
            items.forEach((stu) => {
                const a = document.createElement('a');
                a.href = '#';
                a.className = 'list-group-item list-group-item-action';
                a.textContent = `${stu.full_name} (${stu.admission_number})`;
                a.addEventListener('click', (e) => {
                    e.preventDefault();
                    hidden.value = stu.id;
                    input.value = `${stu.full_name} (${stu.admission_number})`;
                    if (enableBtn) enableBtn.disabled = false;
                    results.classList.add('d-none');
                    window.dispatchEvent(new CustomEvent('student-selected', { detail: stu }));
                });
                results.appendChild(a);
            });
            results.classList.remove('d-none');
        };

        const doSearch = debounce(async () => {
            const q = input.value.trim();
            hidden.value = '';
            if (enableBtn) enableBtn.disabled = true;
            if (q.length < 2) {
                results.classList.add('d-none');
                return;
            }
            results.innerHTML = '<div class="list-group-item text-center text-muted">Searching...</div>';
            results.classList.remove('d-none');

            const urls = [];
            if (customUrl) urls.push(customUrl);
            urls.push(defaultUrl, apiFallback);

            for (const url of urls.filter(Boolean)) {
                try {
                    const res = await fetch(`${url}?q=${encodeURIComponent(q)}`, {
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    });
                    if (!res.ok) throw new Error(`search failed ${res.status}`);
                    const data = await res.json();
                    render(Array.isArray(data) ? data : []);
                    return;
                } catch (e) {
                    // try next URL
                }
            }

            results.innerHTML =
                '<div class="list-group-item text-center text-danger">Search failed. Check connection or permissions.</div>';
            results.classList.remove('d-none');
        });

        input.addEventListener('input', doSearch);
    };

    // Initialize when DOM is ready
    function initAll() {
        document.querySelectorAll('.student-live-search').forEach(initOne);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        // DOM already loaded
        initAll();
    }

    // In case content is loaded dynamically later
    document.addEventListener('student-live-search:init', (e) => {
        if (e.detail?.wrapper) initOne(e.detail.wrapper);
    });
})();

