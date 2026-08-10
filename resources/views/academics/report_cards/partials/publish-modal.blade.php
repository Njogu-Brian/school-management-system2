<div class="modal fade" id="publishReportCardsModal" tabindex="-1" aria-labelledby="publishReportCardsLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="{{ route('academics.report_cards.bulk_publish') }}" id="publishReportCardsForm">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title" id="publishReportCardsLabel">Publish &amp; Send Family Links</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p class="text-muted small">Publishes selected report cards and sends <strong>one family link per family</strong> showing all siblings, invoices, and report cards.</p>
          <div class="mb-3">
            <label class="form-label fw-semibold">Channels <span class="text-danger">*</span></label>
            <div class="d-flex flex-wrap gap-3">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="channels[]" value="sms" id="pubChannelSms" checked>
                <label class="form-check-label" for="pubChannelSms"><i class="bi bi-chat-dots"></i> SMS</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="channels[]" value="whatsapp" id="pubChannelWa" checked>
                <label class="form-check-label" for="pubChannelWa"><i class="bi bi-whatsapp"></i> WhatsApp</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="channels[]" value="email" id="pubChannelEmail" checked>
                <label class="form-check-label" for="pubChannelEmail"><i class="bi bi-envelope"></i> Email</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="channels[]" value="app" id="pubChannelApp">
                <label class="form-check-label" for="pubChannelApp"><i class="bi bi-phone"></i> App notification</label>
              </div>
            </div>
          </div>
          <div class="mb-1">
            <label class="form-label fw-semibold">Message template</label>
            @include('academics.report_cards.partials.notify-template-picker', ['idPrefix' => 'pub'])
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-ghost-strong" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-settings-primary"><i class="bi bi-upload"></i> Publish &amp; Send</button>
        </div>
      </form>
    </div>
  </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
  const modalEl = document.getElementById('publishReportCardsModal');
  if (!modalEl) return;
  const form = document.getElementById('publishReportCardsForm');

  window.openPublishReportCards = function(ids) {
    modalEl.querySelectorAll('input[name="ids[]"]').forEach(el => el.remove());
    (ids || []).forEach(id => {
      const hidden = document.createElement('input');
      hidden.type = 'hidden';
      hidden.name = 'ids[]';
      hidden.value = id;
      form.appendChild(hidden);
    });
    if (!(ids || []).length) {
      alert('Select at least one report card.');
      return;
    }
    new bootstrap.Modal(modalEl).show();
  };

  form?.addEventListener('submit', function(e) {
    const checked = form.querySelectorAll('input[name="channels[]"]:checked');
    if (!checked.length) {
      e.preventDefault();
      alert('Select at least one channel.');
    }
  });
});
</script>
@endpush
