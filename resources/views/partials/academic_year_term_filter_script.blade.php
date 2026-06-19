<script>
(function () {
  if (window.__academicYearTermFilterLoaded) return;
  window.__academicYearTermFilterLoaded = true;

  function yearSelectsIn(form) {
    return Array.from(form.querySelectorAll(
      'select[name="academic_year_id"], select[name="year_id"]'
    ));
  }

  function termSelectsIn(form) {
    return Array.from(form.querySelectorAll('select[name="term_id"]'));
  }

  window.filterTermsByAcademicYear = function (yearSelect, termSelect) {
    if (!yearSelect || !termSelect) return;

    const yearId = yearSelect.value;
    const currentTerm = termSelect.value;
    let valid = false;

    termSelect.querySelectorAll('option').forEach(function (opt) {
      if (!opt.value) {
        opt.hidden = false;
        return;
      }
      const optYear = opt.getAttribute('data-academic-year-id');
      const show = yearId && optYear && String(optYear) === String(yearId);
      opt.hidden = !show;
      if (show && opt.value === currentTerm) {
        valid = true;
      }
    });

    if (!yearId || !valid) {
      termSelect.value = '';
    }
  };

  window.initAcademicYearTermSelects = function (yearSelect, termSelect) {
    if (!yearSelect || !termSelect) return;

    const handler = function () {
      window.filterTermsByAcademicYear(yearSelect, termSelect);
    };

    yearSelect.removeEventListener('change', yearSelect.__academicTermFilterHandler);
    yearSelect.__academicTermFilterHandler = handler;
    yearSelect.addEventListener('change', handler);
    handler();
  };

  function initForm(form) {
    const years = yearSelectsIn(form);
    const terms = termSelectsIn(form);
    if (!years.length || !terms.length) return;

    years.forEach(function (yearSelect) {
      yearSelect.classList.add('academic-year-select');
      terms.forEach(function (termSelect) {
        termSelect.classList.add('academic-term-select');
        window.initAcademicYearTermSelects(yearSelect, termSelect);
      });
    });
  }

  function initAll() {
    document.querySelectorAll('form').forEach(initForm);
  }

  document.addEventListener('DOMContentLoaded', initAll);
})();
</script>
