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
  $addressRaw = setting('report_stamp_address', setting('school_address', 'P. O. Box 10804 - 00100 NAIROBI'));
  $poBox = strtoupper(trim((string) $addressRaw));
  if ($poBox !== '' && ! str_contains($poBox, 'BOX') && ! str_contains($poBox, 'P.O')) {
      $poBox = 'P. O. BOX '.$poBox;
  }
  $phoneRaw = trim((string) setting('report_stamp_phone', setting('school_phone', '0719 396 233')));
  $phoneLine = strtoupper(str_starts_with($phoneRaw, 'TEL') ? $phoneRaw : 'TEL: '.$phoneRaw);
  $stampW = ! empty($isPdf) ? 178 : 210;
  $stampH = ! empty($isPdf) ? 122 : 142;
  $nameSize = ! empty($isPdf) ? 8.6 : 10.2;
  $metaSize = ! empty($isPdf) ? 7.2 : 8.4;
  $dateSize = ! empty($isPdf) ? 9.8 : 11.2;
  $rotate = -1.8;
  $centerX = $stampW / 2;
  $centerY = $stampH / 2;
@endphp

<div class="school-official-stamp" style="display:inline-block; line-height:0; margin:0; padding:0;">
  <svg xmlns="http://www.w3.org/2000/svg"
       width="{{ $stampW }}px"
       height="{{ $stampH }}px"
       viewBox="0 0 {{ $stampW }} {{ $stampH }}"
       role="img"
       aria-label="Official school stamp">
    <defs>
      <filter id="stampInk-{{ md5($stampDateText.$stampW) }}" x="-8%" y="-8%" width="116%" height="116%">
        <feTurbulence type="fractalNoise" baseFrequency="0.95" numOctaves="2" seed="3" result="noise"/>
        <feDisplacementMap in="SourceGraphic" in2="noise" scale="0.65" xChannelSelector="R" yChannelSelector="G"/>
      </filter>
      <pattern id="stampSpeck-{{ md5($stampDateText.$stampW) }}" width="6" height="6" patternUnits="userSpaceOnUse">
        <circle cx="1.2" cy="2.4" r="0.35" fill="{{ $stampBlue }}" opacity="0.08"/>
        <circle cx="4.5" cy="1.1" r="0.28" fill="{{ $stampBlue }}" opacity="0.06"/>
        <circle cx="3.3" cy="4.8" r="0.22" fill="{{ $stampRed }}" opacity="0.05"/>
      </pattern>
    </defs>
    <g transform="rotate({{ $rotate }} {{ $centerX }} {{ $centerY }})" filter="url(#stampInk-{{ md5($stampDateText.$stampW) }})" opacity="0.9">
      <rect x="3.5" y="3.5" width="{{ $stampW - 7 }}" height="{{ $stampH - 7 }}" fill="url(#stampSpeck-{{ md5($stampDateText.$stampW) }})" opacity="0.35"/>
      <rect x="3.5" y="3.5" width="{{ $stampW - 7 }}" height="{{ $stampH - 7 }}" fill="none" stroke="{{ $stampBlue }}" stroke-width="2.1" opacity="0.88"/>
      <rect x="5.5" y="5.5" width="{{ $stampW - 11 }}" height="{{ $stampH - 11 }}" fill="none" stroke="{{ $stampBlue }}" stroke-width="0.6" opacity="0.35"/>

      <text x="{{ $centerX }}" y="22" text-anchor="middle"
            fill="{{ $stampBlue }}" font-family="DejaVu Sans, Arial, Helvetica, sans-serif"
            font-size="{{ $nameSize }}" font-weight="700" letter-spacing="0.6">{{ $nameLine1 }}</text>
      @if($nameLine2 !== '')
        <text x="{{ $centerX }}" y="{{ ! empty($isPdf) ? 33 : 36 }}" text-anchor="middle"
              fill="{{ $stampBlue }}" font-family="DejaVu Sans, Arial, Helvetica, sans-serif"
              font-size="{{ $nameSize }}" font-weight="700" letter-spacing="0.6">{{ $nameLine2 }}</text>
      @endif

      <text x="{{ $centerX }}" y="{{ ! empty($isPdf) ? 52 : 58 }}" text-anchor="middle"
            fill="{{ $stampRed }}" font-family="DejaVu Sans, Arial, Helvetica, sans-serif"
            font-size="{{ $dateSize }}" font-weight="700" letter-spacing="1.1">{{ $stampDateText }}</text>

      <text x="{{ $centerX }}" y="{{ ! empty($isPdf) ? 72 : 82 }}" text-anchor="middle"
            fill="{{ $stampBlue }}" font-family="DejaVu Sans, Arial, Helvetica, sans-serif"
            font-size="{{ $metaSize }}" font-weight="600" letter-spacing="0.35">{{ $poBox }}</text>
      <text x="{{ $centerX }}" y="{{ ! empty($isPdf) ? 86 : 98 }}" text-anchor="middle"
            fill="{{ $stampBlue }}" font-family="DejaVu Sans, Arial, Helvetica, sans-serif"
            font-size="{{ $metaSize }}" font-weight="600" letter-spacing="0.35">{{ $phoneLine }}</text>
    </g>
  </svg>
</div>
