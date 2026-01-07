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
            <div class="col-12">
              <label class="form-label fw-semibold">Select Channels <span class="text-danger">*</span></label>
              <div class="d-flex gap-3">
                <div class="form-check">
                  <input class="form-check-input channel-checkbox" type="checkbox" name="channels[]" value="sms" id="channelSms" checked>
                  <label class="form-check-label" for="channelSms">
                    <i class="bi bi-chat-dots"></i> SMS (link)
                  </label>
                </div>
                <div class="form-check">
                  <input class="form-check-input channel-checkbox" type="checkbox" name="channels[]" value="whatsapp" id="channelWhatsApp" checked>
                  <label class="form-check-label" for="channelWhatsApp">
                    <i class="bi bi-whatsapp"></i> WhatsApp (link)
                  </label>
                </div>
                <div class="form-check">
                  <input class="form-check-input channel-checkbox" type="checkbox" name="channels[]" value="email" id="channelEmail" checked>
                  <label class="form-check-label" for="channelEmail">
                    <i class="bi bi-envelope"></i> Email (PDF attachment)
                  </label>
                </div>
              </div>
              <small class="text-muted d-block mt-2">Select one or more channels to send the document</small>
            </div>
            <div class="col-12 email-only-field">
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

  const channelCheckboxes = modalEl.querySelectorAll('.channel-checkbox');
  const subjectRow = modalEl.querySelector('.email-only-field');
  const form = document.getElementById('sendDocumentForm');

  function toggleSubject() {
    const emailChecked = document.getElementById('channelEmail')?.checked;
    subjectRow?.classList.toggle('d-none', !emailChecked);
  }

  // Toggle subject field when email checkbox changes
  channelCheckboxes.forEach(cb => {
    cb.addEventListener('change', function() {
      if (this.value === 'email') {
        toggleSubject();
      }
      // Ensure at least one channel is selected
      const anyChecked = Array.from(channelCheckboxes).some(c => c.checked);
      if (!anyChecked) {
        this.checked = true; // Re-check if trying to uncheck the last one
      }
    });
  });

  // Validate form submission - ensure at least one channel is selected
  form?.addEventListener('submit', function(e) {
    const checkedChannels = Array.from(channelCheckboxes).filter(cb => cb.checked);
    if (checkedChannels.length === 0) {
      e.preventDefault();
      alert('Please select at least one channel (SMS, WhatsApp, or Email)');
      return false;
    }
  });

  toggleSubject(); // Initial state

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

    // Reset all checkboxes to checked by default
    channelCheckboxes.forEach(cb => cb.checked = true);

    if (defaults.message) document.getElementById('sendDocMessage').value = defaults.message;
    if (defaults.subject) document.getElementById('sendDocSubject').value = defaults.subject;
    
    // If default channel is specified, uncheck others
    if (defaults.channel) {
      channelCheckboxes.forEach(cb => {
        cb.checked = (cb.value === defaults.channel);
      });
      toggleSubject();
    } else {
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

