@php
    // Central branding variables for documents and standalone pages (receipts, invoices, statements, payment links).
    // Use in inline styles / PDFs where CSS variables are not available.
    $brandPrimary   = setting('finance_primary_color', '#3a1a59');
    $brandSecondary = setting('finance_secondary_color', '#14b8a6');
    $brandSuccess   = setting('finance_success_color', '#28a745');
    $brandDanger    = setting('finance_danger_color', '#dc3545');
    $brandMuted     = setting('finance_muted_color', '#6b7280');
    $brandBodyFont  = setting('finance_body_font_size', '13');
    $brandHeadingFont = setting('finance_heading_font_size', '19');
    $brandSmallFont = setting('finance_small_font_size', '11');
    $brandMpesaGreen = setting('finance_mpesa_green', '#007e33');
    $brandPrimaryFontFamily = setting('finance_primary_font', 'Inter');
    $brandHeadingFontFamily = setting('finance_heading_font', 'Poppins');
@endphp
