<?php

namespace App\Http\Controllers;

use App\Models\EmailLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailLogController extends Controller
{
    public function index(Request $request): View
    {
        $logs = EmailLog::query()
            ->with('customer')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('template'), fn ($q) => $q->where('template_key', $request->string('template')))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($q) => $q->where('to', 'like', '%'.$request->string('q').'%')
                ->orWhere('subject', 'like', '%'.$request->string('q').'%')))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('email-logs.index', [
            'logs' => $logs,
            'templates' => EmailLog::query()->distinct()->whereNotNull('template_key')->pluck('template_key'),
        ]);
    }

    public function show(EmailLog $emailLog): View
    {
        return view('email-logs.show', ['log' => $emailLog->load('customer', 'subscription', 'invoice')]);
    }
}
