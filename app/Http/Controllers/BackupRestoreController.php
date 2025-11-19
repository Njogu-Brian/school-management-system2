<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BackupRestoreController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:Super Admin|Admin');
    }

    public function index()
    {
        $backups = $this->getBackupList();
        return view('backup_restore.index', compact('backups'));
    }

    public function create()
    {
        try {
            Artisan::call('backup:run', ['--only-db' => true]);
            $output = Artisan::output();
            
            return back()->with('success', 'Database backup created successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Backup failed: ' . $e->getMessage());
        }
    }

    public function download($filename)
    {
        $path = storage_path('app/backups/' . $filename);
        
        if (!file_exists($path)) {
            return back()->with('error', 'Backup file not found.');
        }

        return response()->download($path);
    }

    public function restore(Request $request)
    {
        $validated = $request->validate([
            'backup_file' => 'required|file|mimes:sql,zip',
        ]);

        // This is a simplified version - in production, use proper backup/restore package
        return back()->with('warning', 'Restore functionality requires additional setup. Please contact system administrator.');
    }

    protected function getBackupList()
    {
        $backupDir = storage_path('app/backups');
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $files = glob($backupDir . '/*.{sql,zip}', GLOB_BRACE);
        $backups = [];

        foreach ($files as $file) {
            $backups[] = [
                'name' => basename($file),
                'size' => filesize($file),
                'created_at' => Carbon::createFromTimestamp(filemtime($file)),
            ];
        }

        usort($backups, fn($a, $b) => $b['created_at'] <=> $a['created_at']);

        return $backups;
    }
}
