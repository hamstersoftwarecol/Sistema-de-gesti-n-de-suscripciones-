<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class BackupController extends Controller
{
    public function __construct(private readonly BackupService $backups) {}

    public function index(): View
    {
        $error = null;

        try {
            $backups = $this->backups->all();
            $databaseSize = filesize($this->backups->databasePath()) ?: 0;
        } catch (Throwable $e) {
            $backups = collect();
            $databaseSize = 0;
            $error = $e->getMessage();
        }

        return view('admin.backups.index', compact('backups', 'databaseSize', 'error'));
    }

    public function store(): RedirectResponse
    {
        try {
            $name = $this->backups->create();
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('Backup :name created.', ['name' => $name]));
    }

    public function download(string $backup): BinaryFileResponse
    {
        try {
            $path = $this->backups->path($backup);
        } catch (Throwable) {
            abort(404);
        }

        return response()->download($path);
    }

    public function restore(string $backup): RedirectResponse
    {
        try {
            $this->backups->restore($backup);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.backups.index')->with('success', __('Database restored from :name. A safety copy of the previous state was created.', ['name' => $backup]));
    }

    public function upload(Request $request): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'max:512000']]);

        try {
            $this->backups->restoreFromUpload($request->file('file'));
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.backups.index')->with('success', __('Database restored from the uploaded file.'));
    }

    public function destroy(string $backup): RedirectResponse
    {
        try {
            $this->backups->delete($backup);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('Backup deleted.'));
    }
}
