{{-- Shared print rules: A4 landscape, repeating table headers, hide chrome --}}
<style>
  @page {
    size: A4 landscape;
    margin: 10mm 12mm;
  }

  @media print {
    .no-print,
    .navbar,
    .settings-sidebar,
    .sidebar,
    aside,
    header.app-header,
    .app-header {
      display: none !important;
    }

    body {
      background: #fff !important;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }

    .settings-page .settings-shell {
      max-width: 100% !important;
      padding: 0 !important;
      margin: 0 !important;
    }

    .settings-card {
      box-shadow: none !important;
      border: none !important;
      break-inside: avoid;
    }

    a[href]:after {
      content: none !important;
    }

    /* Letterhead only when printing */
    .exam-report-letterhead--screen {
      display: none !important;
    }

    .exam-report-letterhead--print {
      display: block !important;
    }

    /* Critical: allow wide tables to print; thead repeats on each page */
    .exam-report-print-root .table-responsive {
      overflow: visible !important;
      max-width: none !important;
    }

    .exam-report-marks-table {
      width: 100% !important;
      max-width: 100% !important;
      table-layout: fixed;
      border-collapse: collapse !important;
      font-size: 8.2pt !important;
      page-break-inside: auto;
    }

    .exam-report-marks-table thead {
      display: table-header-group;
    }

    .exam-report-marks-table tfoot {
      display: table-footer-group;
    }

    .exam-report-marks-table thead th {
      background: #e8e8e8 !important;
      color: #111 !important;
      font-weight: 700;
      border: 1px solid #333 !important;
      padding: 4px 3px !important;
      vertical-align: middle;
      word-wrap: break-word;
      word-break: break-word;
      line-height: 1.1;
    }

    .exam-report-marks-table tbody td {
      border: 1px solid #555 !important;
      padding: 4px 3px !important;
      vertical-align: middle;
      word-wrap: break-word;
      word-break: break-word;
      line-height: 1.15;
    }

    .exam-report-marks-table tbody tr {
      page-break-inside: avoid;
      page-break-after: auto;
    }

    .exam-report-print-root .card-header.d-print-none {
      display: none !important;
    }

    /* Switch to compact header labels in print (Adm/No, Stud/ent) */
    .er-hdr--screen { display: none !important; }
    .er-hdr--print { display: inline !important; }

    /* Multi-column report pages: stack sections for print */
    .exam-report-print-root .row {
      display: block !important;
    }

    .exam-report-print-root .row > [class*="col-"] {
      width: 100% !important;
      max-width: 100% !important;
    }
  }

  /* Screen: hide print-only letterhead block */
  .exam-report-letterhead--print {
    display: none;
  }

  /* Screen: normal (single-line) header labels */
  .er-hdr--print { display: none; }
  .er-hdr--screen { display: inline; }

  /* Column widths tuned to match screenshot layout */
  .exam-report-marks-table .er-col--idx { width: 3%; }
  .exam-report-marks-table .er-col--adm { width: 7.5%; }
  .exam-report-marks-table .er-col--student { width: 20%; }
  .exam-report-marks-table .er-col--score { width: 5.2%; }
  .exam-report-marks-table .er-col--total { width: 5.8%; }
  .exam-report-marks-table .er-col--avg { width: 5.2%; }
  .exam-report-marks-table .er-col--cls { width: 4%; }
  .exam-report-marks-table .er-col--str { width: 4%; }

  .exam-report-marks-table .er-th,
  .exam-report-marks-table .er-td { white-space: normal; }
</style>
