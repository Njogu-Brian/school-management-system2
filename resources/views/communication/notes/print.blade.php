<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Notes – {{ $title }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: Georgia, 'Times New Roman', serif;
            font-size: 12pt;
            line-height: 1.5;
            color: #222;
            margin: 0;
            padding: 20px;
            max-width: 210mm;
            margin-left: auto;
            margin-right: auto;
        }
        .note-block {
            page-break-inside: avoid;
            margin-bottom: 32px;
            padding-bottom: 24px;
            border-bottom: 1px dashed #ccc;
        }
        .note-block:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
        .letterhead {
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 2px solid #333;
        }
        .letterhead-table { width: 100%; border-collapse: collapse; }
        .letterhead-table td { vertical-align: top; }
        .letterhead .logo-cell { width: 90px; vertical-align: middle; }
        .letterhead .logo-cell img { max-width: 80px; max-height: 80px; object-fit: contain; display: block; }
        .letterhead .school-name { font-size: 18pt; font-weight: bold; margin: 0 0 6px 0; line-height: 1.2; }
        .letterhead .school-contacts { font-size: 10pt; color: #555; line-height: 1.4; }
        .note-date { font-size: 11pt; margin-bottom: 6px; }
        .note-title { font-size: 14pt; font-weight: bold; margin-bottom: 12px; text-align: left; }
        .note-body {
            white-space: pre-wrap;
            margin: 0;
            text-align: left;
            line-height: 1.6;
            text-rendering: optimizeLegibility;
        }
        @media print {
            @page {
                size: A4 portrait;
                margin: 15mm;
            }
            body {
                padding: 0;
                max-width: none;
            }
            .note-block {
                page-break-inside: avoid;
                margin-bottom: 24px;
                padding-bottom: 16px;
            }
            .note-block:last-child { margin-bottom: 0; padding-bottom: 0; }
        }
    </style>
</head>
<body>
    @foreach($notes as $note)
        <div class="note-block">
            <div class="letterhead">
                <table class="letterhead-table">
                    <tr>
                        <td class="logo-cell">
                            @if(!empty($branding['logoBase64']))
                                <img src="{{ $branding['logoBase64'] }}" alt="Logo">
                            @endif
                        </td>
                        <td>
                            <div class="school-name">{{ $branding['name'] ?? 'School Name' }}</div>
                            <div class="school-contacts">
                                @if(!empty($branding['address'])){{ $branding['address'] }}@endif
                                @if(!empty($branding['phone'])) &nbsp;•&nbsp; Tel: {{ $branding['phone'] }} @endif
                                @if(!empty($branding['email'])) &nbsp;•&nbsp; {{ $branding['email'] }} @endif
                                @if(!empty($branding['website'])) &nbsp;•&nbsp; {{ $branding['website'] }} @endif
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="note-date">{{ $date }}</div>
            <div class="note-title">{{ $title }}</div>
            <div class="note-body">{{ $note['body'] }}</div>
        </div>
    @endforeach

    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>
