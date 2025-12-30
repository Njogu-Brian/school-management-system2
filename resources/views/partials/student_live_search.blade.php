@php
  // Props:
  // $hiddenInputId (required): id for hidden input to set selected student id
  // $displayInputId (required): id for visible text input
  // $resultsId (required): id for results container
  // $enableButtonId (optional): id of a button to enable when a student is selected
  // $placeholder (optional): placeholder text
  // $initialLabel (optional): prefilled display value
  $hiddenInputId = $hiddenInputId ?? 'selectedStudentId';
  $displayInputId = $displayInputId ?? 'studentLiveSearch';
  $resultsId = $resultsId ?? 'studentLiveResults';
  $enableButtonId = $enableButtonId ?? null;
  $placeholder = $placeholder ?? 'Type name or admission #';
  $initialLabel = $initialLabel ?? '';
@endphp

<input type="hidden" id="{{ $hiddenInputId }}" name="student_id" value="{{ old('student_id', request('student_id')) }}">
<input type="text" id="{{ $displayInputId }}" class="form-control"
       value="{{ $initialLabel }}"
       placeholder="{{ $placeholder }}"
       data-search-url="{{ $searchUrl ?? '' }}">
<div id="{{ $resultsId }}" class="list-group shadow-sm mt-1 d-none" style="max-height: 220px; overflow-y: auto;"></div>
<small class="text-muted">Start typing; results appear below automatically.</small>

@push('scripts')
<script>
(function(){
    const input = document.getElementById('{{ $displayInputId }}');
    const results = document.getElementById('{{ $resultsId }}');
    const hiddenId = document.getElementById('{{ $hiddenInputId }}');
    const enableBtn = {{ $enableButtonId ? 'document.getElementById("'.$enableButtonId.'")' : 'null' }};
    let t=null;
    // Use relative URLs to avoid CORS when APP_URL points to a different host
    const defaultUrl = '/students/search';
    const apiFallback = '/api/students/search';

    const render = (items) => {
        results.innerHTML = '';
        if (!items.length) {
            results.classList.add('d-none');
            return;
        }
        items.forEach(stu => {
            const a = document.createElement('a');
            a.href = '#';
            a.className = 'list-group-item list-group-item-action';
            a.textContent = `${stu.full_name} (${stu.admission_number})`;
            a.addEventListener('click', (e)=>{
                e.preventDefault();
                hiddenId.value = stu.id;
                input.value = `${stu.full_name} (${stu.admission_number})`;
                if (enableBtn) enableBtn.disabled = false;
                results.classList.add('d-none');
                window.dispatchEvent(new CustomEvent('student-selected', { detail: stu }));
            });
            results.appendChild(a);
        });
        results.classList.remove('d-none');
    };

    input.addEventListener('input', ()=>{
        clearTimeout(t);
        t = setTimeout(async ()=>{
            const q = input.value.trim();
            hiddenId.value = '';
            if (enableBtn) enableBtn.disabled = true;
            if (q.length < 2) { results.classList.add('d-none'); return; }
            results.innerHTML = '<div class="list-group-item text-center text-muted">Searching...</div>';
            results.classList.remove('d-none');
            const urls = [];
            const customUrl = input.dataset.searchUrl;
            if (customUrl) urls.push(customUrl);
            urls.push(defaultUrl);
            urls.push(apiFallback);
            for (const url of urls.filter(Boolean)) {
                try {
                    const res = await fetch(`${url}?q=${encodeURIComponent(q)}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        credentials: 'same-origin'
                    });
                    if (!res.ok) throw new Error(`search failed ${res.status}`);
                    const text = await res.text();
                    let data;
                    try {
                        data = JSON.parse(text);
                    } catch (parseErr) {
                        console.warn('Student search response not JSON', { url, text: text?.slice(0, 200) });
                        throw parseErr;
                    }
                    render(Array.isArray(data) ? data : []);
                    return;
                } catch (e) {
                    // try next url
                }
            }
            results.innerHTML = '<div class="list-group-item text-center text-danger">Search failed. Check connection or permissions.</div>';
            results.classList.remove('d-none');
        }, 600);
    });
})();
</script>
@endpush

