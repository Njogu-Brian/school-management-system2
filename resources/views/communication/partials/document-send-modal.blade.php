<div class="modal fade" id="sendDocumentModal" tabindex="-1" aria-labelledby="sendDocumentLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="{{ route('communication.send.document') }}" id="sendDocumentForm">
        @csrf
        <input type="hidden" name="type" id="sendDocType" value="">
        <input type="hidden" name="ids[]" id="sendDocIds" value="">
        <div class="modal-header">
          <h5 class="modal-title" id="sendDocumentLabel">Send via SMS / Email / WhatsApp</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label fw-semibold">Channel</label>
              <select name="channel" id="sendDocChannel" class="form-select" required>
                <option value="sms">SMS (link)</option>
                <option value="whatsapp">WhatsApp (link)</option>
                <option value="email">Email (PDF attachment)</option>
              </select>
            </div>
            <div class="col-md-8 email-only-field d-none">
              <label class="form-label fw-semibold">Email Subject</label>
              <input type="text" class="form-control" name="subject" id="sendDocSubject" value="School Update">
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold">Message</label>
              <textarea name="message" id="sendDocMessage" class="form-control" rows="4" required>Hi, please find the document attached/link below.</textarea>
              <small class="text-muted">For email we attach PDF; SMS/WhatsApp include the link.</small>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-ghost-strong" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-settings-primary">Send</button>
        </div>
      </form>
    </div>
  </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
  const modalEl = document.getElementById('sendDocumentModal');
  if (!modalEl) return;

  const channelSelect = document.getElementById('sendDocChannel');
  const subjectRow = modalEl.querySelector('.email-only-field');

  function toggleSubject() {
    const show = channelSelect.value === 'email';
    subjectRow?.classList.toggle('d-none', !show);
  }
  channelSelect?.addEventListener('change', toggleSubject);
  toggleSubject();

  window.openSendDocument = function(type, ids, defaults = {}) {
    document.getElementById('sendDocType').value = type;
    document.getElementById('sendDocIds').value = '';

    // Clear existing hidden ids[]
    modalEl.querySelectorAll('input[name="ids[]"]').forEach(el => el.remove());

    (ids || []).forEach(id => {
      const hidden = document.createElement('input');
      hidden.type = 'hidden';
      hidden.name = 'ids[]';
      hidden.value = id;
      document.getElementById('sendDocumentForm').appendChild(hidden);
    });

    if (defaults.message) document.getElementById('sendDocMessage').value = defaults.message;
    if (defaults.subject) document.getElementById('sendDocSubject').value = defaults.subject;
    if (defaults.channel) {
      document.getElementById('sendDocChannel').value = defaults.channel;
      toggleSubject();
    }

    const modal = new bootstrap.Modal(modalEl);
    modal.show();
  }

  window.collectCheckedIds = function(selector) {
    return Array.from(document.querySelectorAll(selector))
      .filter(cb => cb.checked)
      .map(cb => cb.value);
  }
});
</script>
@endpush

