@php
  $stampAt = $stampDate ?? now();
  $stampDateText = strtoupper($stampAt->format('d M Y'));
  $stampBlue = '#1a4f9c';
  $stampRed = '#c62828';
  $fullName = strtoupper(trim(setting('school_name', 'ROYAL KINGS PREMIER SCHOOL LTD')));
  $nameLine1 = setting('report_stamp_line1');
  $nameLine2 = setting('report_stamp_line2');
  if (! filled($nameLine1)) {
      if (preg_match('/^(.+?\bSCHOOL)\s+(.+)$/i', $fullName, $m)) {
          $nameLine1 = strtoupper(trim($m[1]));
          $nameLine2 = strtoupper(trim($m[2]));
      } elseif (preg_match('/^(.+?\bPREMIER)\s+(.+)$/i', $fullName, $m)) {
          $nameLine1 = strtoupper(trim($m[1]));
          $nameLine2 = strtoupper(trim($m[2]));
      } else {
          $words = preg_split('/\s+/', $fullName) ?: [];
          $mid = max(1, (int) ceil(count($words) / 2));
          $nameLine1 = strtoupper(implode(' ', array_slice($words, 0, $mid)));
          $nameLine2 = strtoupper(implode(' ', array_slice($words, $mid)));
      }
  }
  $nameLine1 = strtoupper(trim((string) $nameLine1));
  $nameLine2 = strtoupper(trim((string) $nameLine2));
  $poBox = strtoupper(trim((string) setting('report_stamp_address', 'P.O BOX 10804-00100 NRB')));
  $phoneRaw = trim((string) setting('report_stamp_phone', setting('school_phone', '0719 396 233')));
  $phoneLine = strtoupper(str_starts_with($phoneRaw, 'TEL') ? $phoneRaw : 'TEL: '.$phoneRaw);
  $isPdfMode = ! empty($isPdf);
  $fontFamily = $isPdfMode ? 'DejaVu Sans, sans-serif' : 'Arial, Helvetica, sans-serif';
  $padX = $isPdfMode ? '10px' : '14px';
  $padY = $isPdfMode ? '5px' : '6px';
  $nameSize = $isPdfMode ? '8.5px' : '10px';
  $dateSize = $isPdfMode ? '10px' : '11.5px';
  $metaSize = $isPdfMode ? '7px' : '8.5px';
  $stampWidth = $isPdfMode ? '188px' : '210px';
@endphp

{{-- HTML table stamp: DomPDF does not render SVG stamps reliably --}}
<table cellpadding="0" cellspacing="0" role="presentation" aria-label="Official school stamp"
       style="width:{{ $stampWidth }}; border-collapse:collapse; margin:0; opacity:0.92; font-family:{{ $fontFamily }};">
  <tr>
    <td style="border:2.5px solid {{ $stampBlue }}; padding:{{ $padY }} {{ $padX }}; text-align:center; background:rgba(26,79,156,0.04);">
      <table cellpadding="0" cellspacing="0" role="presentation" style="width:100%; border-collapse:collapse;">
        <tr>
          <td style="text-align:center; color:{{ $stampBlue }}; font-weight:700; font-size:{{ $nameSize }}; letter-spacing:0.6px; line-height:1.35; padding-bottom:3px;">
            {{ $nameLine1 }}@if($nameLine2 !== '')<br>{{ $nameLine2 }}@endif
          </td>
        </tr>
        <tr>
          <td style="text-align:center; color:{{ $stampRed }}; font-weight:700; font-size:{{ $dateSize }}; letter-spacing:1px; line-height:1.3; padding:4px 0;">
            {{ $stampDateText }}
          </td>
        </tr>
        <tr>
          <td style="text-align:center; color:{{ $stampBlue }}; font-weight:600; font-size:{{ $metaSize }}; letter-spacing:0.35px; line-height:1.35; padding-top:2px;">
            {{ $poBox }}
          </td>
        </tr>
        <tr>
          <td style="text-align:center; color:{{ $stampBlue }}; font-weight:600; font-size:{{ $metaSize }}; letter-spacing:0.35px; line-height:1.35; padding-top:2px;">
            {{ $phoneLine }}
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
