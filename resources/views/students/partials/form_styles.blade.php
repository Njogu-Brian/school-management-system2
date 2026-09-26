<style>
.student-form-page .settings-shell {
  max-width: 1100px;
}
.student-admission-form {
  overflow: visible;
}
.student-form-section {
  border: 1px solid rgba(15, 23, 42, 0.08);
  border-radius: 1rem;
  background: #fff;
  padding: 1.25rem 1.35rem;
  margin-bottom: 1.25rem;
  box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
}
.student-form-section__title {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  margin: 0 0 1rem;
  font-size: 0.95rem;
  font-weight: 700;
  letter-spacing: 0.02em;
  text-transform: uppercase;
  color: #312e81;
}
.student-form-section__title i {
  width: 2rem;
  height: 2rem;
  border-radius: 0.65rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: rgba(99, 102, 241, 0.12);
  color: #4f46e5;
}
.student-form-section .form-label {
  font-weight: 600;
  color: #334155;
  margin-bottom: 0.35rem;
}
.student-form-body > h6 {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin: 0 0 1rem;
  padding: 0.65rem 0.85rem;
  border-radius: 0.75rem;
  font-size: 0.9rem;
  font-weight: 700;
  letter-spacing: 0.03em;
  text-transform: uppercase;
  color: #312e81;
  background: linear-gradient(90deg, rgba(99, 102, 241, 0.12), rgba(99, 102, 241, 0.02));
}
.student-form-body > .row {
  margin-bottom: 0.25rem;
}
.student-form-body > hr {
  border: 0;
  border-top: 1px dashed rgba(148, 163, 184, 0.55);
  margin: 1.5rem 0;
}
.student-admission-form .card-body {
  padding: 1.35rem 1.35rem 0.5rem;
}
.student-form-section .form-control,
.student-form-section .form-select,
.student-form-body .form-control,
.student-form-body .form-select {
  border-radius: 0.7rem;
  min-height: 2.65rem;
  border-color: #cbd5e1;
}
.student-form-body .form-control:focus,
.student-form-body .form-select:focus {
  border-color: #6366f1;
  box-shadow: 0 0 0 0.2rem rgba(99, 102, 241, 0.18);
}
.student-form-body .form-control.is-invalid,
.student-form-body .form-select.is-invalid,
.student-form-body .form-check-input.is-invalid {
  border-color: #dc2626 !important;
  box-shadow: 0 0 0 0.15rem rgba(220, 38, 38, 0.18);
  background-color: #fef2f2;
}
.student-form-body .invalid-feedback {
  display: block;
  color: #b91c1c;
  font-weight: 500;
}
.student-form-error-banner {
  display: flex;
  gap: 0.85rem;
  align-items: flex-start;
  padding: 0.95rem 1.1rem;
  margin-bottom: 1rem;
  border-radius: 0.9rem;
  border: 1px solid #fecaca;
  background: linear-gradient(180deg, #fef2f2, #fff1f2);
  color: #7f1d1d;
}
.student-form-error-banner__icon {
  font-size: 1.25rem;
  line-height: 1;
  margin-top: 0.1rem;
}
.student-form-footer {
  position: sticky;
  bottom: 0;
  z-index: 20;
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  justify-content: flex-end;
  align-items: center;
  padding: 1rem 1.25rem;
  margin: 0 -0.25rem -0.25rem;
  border-top: 1px solid rgba(15, 23, 42, 0.08);
  background: rgba(255, 255, 255, 0.96);
  backdrop-filter: blur(8px);
}
.student-sibling-banner {
  display: none;
  margin-top: 0.75rem;
  padding: 0.75rem 0.9rem;
  border-radius: 0.75rem;
  background: #ecfdf5;
  border: 1px solid #a7f3d0;
  color: #065f46;
}
.student-sibling-banner.is-visible {
  display: block;
}
.student-form-field-flash {
  animation: studentFieldFlash 1.1s ease;
}
@keyframes studentFieldFlash {
  0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.55); }
  100% { box-shadow: 0 0 0 0.35rem rgba(16, 185, 129, 0); }
}
@media (max-width: 767.98px) {
  .student-form-section {
    padding: 1rem;
    border-radius: 0.85rem;
  }
  .student-form-footer {
    position: static;
    justify-content: stretch;
  }
  .student-form-footer .btn {
    width: 100%;
  }
}
</style>
