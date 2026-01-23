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
        const includeAlumniArchived = wrapper.dataset.includeAlumniArchived === '1';

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
                
                // Add badges for alumni/archived if applicable
                let badges = '';
                if (stu.is_alumni) {
                    badges += ' <span class="badge bg-warning text-dark">Alumni</span>';
                }
                if (stu.is_archived) {
                    badges += ' <span class="badge bg-secondary">Archived</span>';
                }
                
                // Display with class if available
                const classDisplay = (stu.classroom_name && stu.classroom_name.trim() !== '') 
                    ? ` - ${stu.classroom_name}` 
                    : '';
                a.innerHTML = `${stu.full_name} (${stu.admission_number})${classDisplay}${badges}`;
                a.addEventListener('click', (e) => {
                    e.preventDefault();
                    hidden.value = stu.id;
                    const displayValue = stu.classroom_name 
                        ? `${stu.full_name} (${stu.admission_number}) - ${stu.classroom_name}`
                        : `${stu.full_name} (${stu.admission_number})`;
                    input.value = displayValue;
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

            // Build query string with include_alumni_archived parameter if needed
            const queryParams = new URLSearchParams({ q });
            if (includeAlumniArchived) {
                queryParams.append('include_alumni_archived', '1');
            }

            // When including alumni/archived, ONLY use API route (requires auth)
            // Otherwise try public route first, then API as fallback
            const urls = [];
            if (customUrl) urls.push(customUrl);
            if (includeAlumniArchived) {
                // When alumni/archived is needed, only use authenticated API route
                urls.push(apiFallback);
            } else {
                urls.push(defaultUrl, apiFallback); // Public first for regular search
            }

            // Get CSRF token from meta tag if available
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            
            let lastError = null;
            let lastStatusCode = null;
            for (const url of urls.filter(Boolean)) {
                try {
                    const headers = {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    };
                    
                    // Add CSRF token if available (Laravel might check it for authenticated routes)
                    if (csrfToken) {
                        headers['X-CSRF-TOKEN'] = csrfToken;
                    }
                    
                    const res = await fetch(`${url}?${queryParams.toString()}`, {
                        headers,
                        credentials: 'same-origin',
                    });
                    
                    if (!res.ok) {
                        lastError = `HTTP ${res.status}`;
                        lastStatusCode = res.status;
                        // If authentication is required (401/403) and we're trying alumni/archived, don't fall back
                        if (includeAlumniArchived && (res.status === 401 || res.status === 403)) {
                            break;
                        }
                        continue; // try next URL
                    }
                    const data = await res.json();
                    render(Array.isArray(data) ? data : []);
                    return;
                } catch (e) {
                    lastError = e.message;
                    // If it's a network error and we're trying alumni/archived, don't fall back
                    if (includeAlumniArchived && e.name === 'TypeError') {
                        break;
                    }
                    // try next URL
                }
            }

            // All URLs failed
            console.error('Student search failed for all URLs:', urls, 'Last error:', lastError, 'Status:', lastStatusCode);
            
            let errorMessage = 'Search failed. Check connection or permissions.';
            if (includeAlumniArchived && lastStatusCode === 401) {
                errorMessage = 'Authentication required. Please refresh the page and try again.';
            } else if (includeAlumniArchived && lastStatusCode === 403) {
                errorMessage = 'Permission denied. You may not have access to search archived/alumni students.';
            }
            
            results.innerHTML = `<div class="list-group-item text-center text-danger">${errorMessage}</div>`;
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

