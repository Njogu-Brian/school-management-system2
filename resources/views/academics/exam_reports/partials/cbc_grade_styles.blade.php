@once
<style>
  .cbc-grade-badge {
    display: inline-block;
    font-size: 0.68rem;
    font-weight: 600;
    line-height: 1.25;
    padding: 0.2rem 0.55rem;
    border-radius: 999px;
    white-space: nowrap;
    max-width: 100%;
    border: 1px solid transparent;
  }
  .cbc-grade-badge--wide {
    font-size: 0.72rem;
    padding: 0.28rem 0.65rem;
    margin-top: 0.2rem;
  }
  .cbc-grade--below {
    background: #fee2e2;
    color: #991b1b;
    border-color: #fecaca;
  }
  .cbc-grade--approaching {
    background: #ffedd5;
    color: #c2410c;
    border-color: #fed7aa;
  }
  .cbc-grade--meeting {
    background: #ede9fe;
    color: #6d28d9;
    border-color: #ddd6fe;
  }
  .cbc-grade--exceeding {
    background: #dcfce7;
    color: #15803d;
    border-color: #bbf7d0;
  }
  .mark-sheet-score {
    font-weight: 600;
    font-variant-numeric: tabular-nums;
  }
  .mark-sheet-stream-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.2rem 0.55rem;
    border-radius: 999px;
    background: #f5f3ff;
    color: #5b21b6;
    border: 1px solid #ddd6fe;
  }
  .class-mean-row td {
    background: #f8fafc;
    border-top: 2px solid #e2e8f0;
  }
  .exam-results-row .student-meta {
    font-size: 0.8rem;
    color: #6b7280;
  }
  .exam-entry-list {
    display: flex;
    flex-direction: column;
  }
  .exam-entry-row {
    display: grid;
    grid-template-columns: minmax(200px, 1.4fr) minmax(160px, 0.9fr) minmax(180px, 1.2fr) minmax(150px, 0.8fr);
    gap: 1rem;
    align-items: center;
    padding: 0.85rem 1.25rem;
    border-bottom: 1px solid #f1f5f9;
  }
  .exam-entry-row:last-child {
    border-bottom: none;
  }
  @media (max-width: 991px) {
    .exam-entry-row {
      grid-template-columns: 1fr;
      gap: 0.65rem;
    }
  }
  .student-avatar {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.35rem;
    height: 2.35rem;
    border-radius: 50%;
    font-size: 0.78rem;
    font-weight: 700;
    color: #fff;
    flex-shrink: 0;
    background: linear-gradient(135deg, #7c3aed, #a78bfa);
  }
  .student-avatar--alt {
    background: linear-gradient(135deg, #0d9488, #5eead4);
  }
  .student-avatar--alt2 {
    background: linear-gradient(135deg, #db2777, #f472b6);
  }
  .exam-entry-student {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    min-width: 0;
  }
  .exam-entry-score {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    flex-wrap: wrap;
  }
  .exam-entry-score .form-control {
    width: 4.5rem;
    text-align: center;
  }
  .exam-entry-score .max-marks {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 3rem;
    padding: 0.35rem 0.5rem;
    border-radius: 0.375rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    font-weight: 600;
    color: #475569;
  }
  .exam-entry-grade {
    display: flex;
    justify-content: flex-end;
  }
  @media (max-width: 991px) {
    .exam-entry-grade {
      justify-content: flex-start;
    }
  }
  .class-mean-bar {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.65rem;
    padding: 1rem 1.25rem;
    background: #f8fafc;
    border-top: 2px solid #e2e8f0;
    font-size: 0.95rem;
  }
</style>
@endonce
