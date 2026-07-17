<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\ActivityLogService;
use App\Services\DatabaseBackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SettingController extends Controller implements HasMiddleware
{
    public function __construct(
        protected DatabaseBackupService $backupService,
        protected ActivityLogService $activityLog,
    ) {
    }

    public static function middleware(): array
    {
        return [new Middleware('role:super_admin')];
    }

    public function index(): View
    {
        return view('settings.index', [
            'settings' => [
                'outlet_name' => Setting::get('outlet_name', config('app.name')),
                'outlet_address' => Setting::get('outlet_address', ''),
                'outlet_logo' => Setting::get('outlet_logo', ''),
                'operational_hours' => Setting::get('operational_hours', ''),
            ],
            'backups' => $this->backupService->listBackups(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'outlet_name' => ['required', 'string', 'max:150'],
            'outlet_address' => ['nullable', 'string'],
            'operational_hours' => ['nullable', 'string', 'max:100'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:1024'],
        ]);

        Setting::set('outlet_name', $data['outlet_name']);
        Setting::set('outlet_address', $data['outlet_address'] ?? '');
        Setting::set('operational_hours', $data['operational_hours'] ?? '');

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('settings', 'public');
            Setting::set('outlet_logo', $path);
        }

        $this->activityLog->log(Auth::user(), 'update', 'setting', 'Memperbarui pengaturan outlet.');

        return back()->with('success', 'Pengaturan berhasil disimpan.');
    }

    public function backup(): mixed
    {
        $path = $this->backupService->backup();

        $this->activityLog->log(Auth::user(), 'backup', 'setting', 'Membuat backup database.');

        return Storage::disk('local')->download($path);
    }

    public function restore(Request $request): RedirectResponse
    {
        $request->validate([
            'backup_file' => ['required', 'file', 'mimes:sql', 'max:51200'],
        ]);

        $content = file_get_contents($request->file('backup_file')->getRealPath());
        $this->backupService->restore($content);

        $this->activityLog->log(Auth::user(), 'restore', 'setting', 'Merestore database dari file backup.');

        return back()->with('success', 'Database berhasil direstore.');
    }

    public function downloadBackup(string $filename): mixed
    {
        $path = 'backups/'.$filename;

        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path);
    }
}
