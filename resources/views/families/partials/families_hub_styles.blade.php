{{-- Shared polish for Families hub pages (index, integrity, missing contacts, link, manage). --}}
<style>
  .families-hub .settings-shell {
    max-width: 1200px;
    margin-left: auto;
    margin-right: auto;
  }
  .families-hub .families-hero-card {
    border-radius: 1rem;
    border: 1px solid rgba(var(--bs-body-color-rgb), 0.08);
    background: linear-gradient(145deg, rgba(var(--bs-primary-rgb), 0.06), transparent 55%);
  }
  .families-hub .families-search-panel .input-group-text {
    border-radius: 0.5rem 0 0 0.5rem;
  }
  .families-hub .families-search-panel .form-control {
    border-radius: 0 0.5rem 0.5rem 0;
    min-height: 2.75rem;
  }
  .families-hub .table-card-wrap {
    border-radius: 0.65rem;
    overflow: hidden;
    border: 1px solid rgba(var(--bs-body-color-rgb), 0.06);
  }
  .families-hub .table-modern tbody tr:last-child td {
    border-bottom: none;
  }
  @media (max-width: 767.98px) {
    .families-hub .settings-shell {
      padding-left: 0.65rem;
      padding-right: 0.65rem;
    }
    .families-hub .page-header h1 {
      font-size: 1.35rem;
    }
    .families-hub .stack-buttons-sm {
      flex-direction: column;
      align-items: stretch !important;
    }
    .families-hub .stack-buttons-sm .btn {
      width: 100%;
      justify-content: center;
    }
    .families-hub .table-modern {
      font-size: 0.8125rem;
    }
    .families-hub .table-modern th,
    .families-hub .table-modern td {
      vertical-align: top;
    }
    /* Improve modal usability on small screens */
    .modal-quick-contact .modal-dialog {
      margin: 0.5rem;
      max-width: calc(100vw - 1rem);
    }
  }
</style>
