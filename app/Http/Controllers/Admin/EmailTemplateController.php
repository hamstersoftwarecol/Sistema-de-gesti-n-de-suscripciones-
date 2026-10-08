<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\GeminiException;
use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use App\Services\GeminiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailTemplateController extends Controller
{
    public function index(): View
    {
        return view('admin.email-templates.index', ['templates' => EmailTemplate::query()->orderBy('name')->get()]);
    }

    public function edit(EmailTemplate $emailTemplate): View
    {
        return view('admin.email-templates.edit', ['template' => $emailTemplate]);
    }

    public function update(Request $request, EmailTemplate $emailTemplate): RedirectResponse
    {
        $emailTemplate->update($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:20000'],
            'is_active' => ['boolean'],
        ]));

        return redirect()->route('admin.email-templates.index')->with('success', __('Template saved.'));
    }

    /** Render the template with sample data. */
    public function preview(Request $request, EmailTemplate $emailTemplate): JsonResponse
    {
        $template = (new EmailTemplate)->fill($request->only(['subject', 'body']));

        $rendered = $template->render([
            'company_name' => setting('company_name', config('app.name')),
            'customer_name' => 'María Pérez',
            'customer_email' => 'maria@example.com',
            'subscription_reference' => 'SUB-00042',
            'plan_name' => 'CRM Cloud — Profesional',
            'amount' => money(79),
            'renewal_date' => fdate(today()->addDays(7)),
            'days_left' => 7,
            'invoice_number' => 'FAC-00123',
            'invoice_total' => money(94.01),
            'invoice_due_date' => fdate(today()->addDays(7)),
            'invoice_balance' => money(94.01),
            'payment_amount' => money(94.01),
            'portal_url' => url('/portal'),
            'login_email' => 'maria@example.com',
            'login_password' => '••••••••',
        ]);

        return response()->json([
            'subject' => $rendered['subject'],
            'html' => view('emails.template', ['subjectLine' => $rendered['subject'], 'bodyText' => $rendered['body']])->render(),
        ]);
    }

    public function aiDraft(Request $request, EmailTemplate $emailTemplate, GeminiService $gemini): JsonResponse
    {
        $data = $request->validate(['instructions' => ['nullable', 'string', 'max:1000']]);

        try {
            $body = $gemini->draftEmail($emailTemplate->name.'. '.($data['instructions'] ?? ''));
        } catch (GeminiException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['body' => $body]);
    }
}
