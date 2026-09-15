<?php

namespace App\Http\Controllers;

use App\Services\MobileAppApkService;
use Illuminate\Http\Request;

class AppDownloadController extends Controller
{
    public function playStore()
    {
        return response()->view('auth.open-play-store', [
            'package' => (string) config('app.play_store_package', 'com.royalkingsschools.users'),
            'httpsUrl' => (string) config('app.play_store_url'),
        ]);
    }

    public function apk(Request $request, MobileAppApkService $apk)
    {
        $stored = $apk->downloadResponse();
        if ($stored) {
            return $stored;
        }

        $local = $this->localApkPath();
        if ($local) {
            return response()->download($local, 'RoyalKingsUsers.apk', [
                'Content-Type' => 'application/vnd.android.package-archive',
                'Cache-Control' => 'no-store, no-cache, must-revalidate',
            ]);
        }

        $remote = $apk->publicUrl();
        if ($remote && ! str_contains($remote, '/app/android.apk')) {
            return redirect()->away($remote);
        }

        return redirect()
            ->route('app.play-store')
            ->with('status', 'Please install Royal Kings Users from Google Play.');
    }

    private function localApkPath(): ?string
    {
        $candidates = [
            public_path('downloads/royal-kings-users.apk'),
            storage_path('app/public/downloads/royal-kings-users.apk'),
            storage_path('app/downloads/royal-kings-users.apk'),
        ];
        foreach ($candidates as $path) {
            if (is_file($path) && is_readable($path)) {
                return $path;
            }
        }

        return null;
    }
}
