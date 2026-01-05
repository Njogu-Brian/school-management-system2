<!DOCTYPE html>
<html lang="en">
    <head>
        @php
            $settings = \App\Models\Setting::whereIn('key', ['school_name', 'school_logo', 'favicon'])->pluck('value', 'key');
            $appName = $settings['school_name'] ?? config('app.name', 'School Management System');
            $logoSetting = $settings['school_logo'] ?? null;
            $faviconSetting = $settings['favicon'] ?? $logoSetting;

            $resolveImage = function ($filename) {
                if (!$filename) return null;
                if (\Illuminate\Support\Facades\Storage::disk('public')->exists($filename)) {
                    return \Illuminate\Support\Facades\Storage::url($filename);
                }
                if (file_exists(public_path('images/'.$filename))) {
                    return asset('images/'.$filename);
                }
                return null;
            };

            $faviconUrl = $resolveImage($faviconSetting) ?? asset('images/logo.png');
        @endphp
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>@yield('title') | {{ $appName }}</title>
        <link rel="icon" href="{{ $faviconUrl }}">

        <!-- Styles -->
        <style>
            html, body {
                background-color: #fff;
                color: #636b6f;
                font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
                font-weight: 100;
                height: 100vh;
                margin: 0;
            }

            .full-height {
                height: 100vh;
            }

            .flex-center {
                align-items: center;
                display: flex;
                justify-content: center;
            }

            .position-ref {
                position: relative;
            }

            .content {
                text-align: center;
            }

            .title {
                font-size: 36px;
                padding: 20px;
            }
        </style>
    </head>
    <body>
        <div class="flex-center position-ref full-height">
            <div class="content">
                <div class="title">
                    @yield('message')
                </div>
            </div>
        </div>
    </body>
</html>
