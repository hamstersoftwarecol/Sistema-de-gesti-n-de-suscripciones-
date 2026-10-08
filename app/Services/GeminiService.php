<?php

namespace App\Services;

use App\Exceptions\GeminiException;
use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\Customer;
use App\Models\Setting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Thin client for the Google Gemini "generateContent" REST API.
 */
class GeminiService
{
    /** Number of previous messages sent back as conversation memory. */
    public const HISTORY_LIMIT = 20;

    public function __construct(private readonly BusinessMetrics $metrics) {}

    public function apiKey(): ?string
    {
        return Setting::get('gemini_api_key') ?: config('services.gemini.key');
    }

    public function model(): string
    {
        return Setting::get('gemini_model') ?: config('services.gemini.model', 'gemini-2.5-flash');
    }

    public function isConfigured(): bool
    {
        return filled($this->apiKey());
    }

    /**
     * Call Gemini with a list of {role, text} turns.
     *
     * @param  array<int, array{role: string, text: string}>  $turns
     * @return array{text: string, tokens: int|null}
     */
    public function generate(array $turns, ?string $systemInstruction = null, float $temperature = 0.7): array
    {
        if (! $this->isConfigured()) {
            throw new GeminiException(__('Gemini AI is not configured. Add your API key in Settings → Integrations.'));
        }

        $payload = [
            'contents' => array_map(fn (array $turn) => [
                'role' => $turn['role'] === 'model' ? 'model' : 'user',
                'parts' => [['text' => $turn['text']]],
            ], $turns),
            'generationConfig' => [
                'temperature' => $temperature,
                'maxOutputTokens' => 4096,
            ],
        ];

        if ($systemInstruction) {
            $payload['systemInstruction'] = ['parts' => [['text' => $systemInstruction]]];
        }

        $url = rtrim(config('services.gemini.endpoint'), '/').'/models/'.$this->model().':generateContent';

        try {
            $response = Http::withHeaders(['x-goog-api-key' => $this->apiKey()])
                ->acceptJson()
                ->timeout(90)
                ->post($url, $payload);
        } catch (ConnectionException $e) {
            throw new GeminiException(__('Could not connect to Gemini: :message', ['message' => $e->getMessage()]), 0, $e);
        }

        if ($response->failed()) {
            $message = $response->json('error.message') ?? ('HTTP '.$response->status());

            throw new GeminiException(__('Gemini error: :message', ['message' => $message]));
        }

        $text = collect($response->json('candidates.0.content.parts', []))
            ->pluck('text')
            ->filter()
            ->implode('');

        if (trim($text) === '') {
            $reason = $response->json('promptFeedback.blockReason') ?? $response->json('candidates.0.finishReason') ?? 'EMPTY';

            throw new GeminiException(__('Gemini returned an empty answer (:reason).', ['reason' => $reason]));
        }

        return [
            'text' => trim($text),
            'tokens' => $response->json('usageMetadata.totalTokenCount'),
        ];
    }

    /**
     * Store the user's message, ask Gemini with the conversation history and store the answer.
     */
    public function reply(AiConversation $conversation, string $message): AiMessage
    {
        $question = $conversation->messages()->create(['role' => 'user', 'content' => $message]);

        $history = $conversation->messages()
            ->latest('id')
            ->take(self::HISTORY_LIMIT)
            ->get()
            ->reverse()
            ->map(fn (AiMessage $m) => ['role' => $m->role, 'text' => $m->content])
            ->values()
            ->all();

        try {
            $result = $this->generate($history, $this->assistantInstruction());
        } catch (GeminiException $e) {
            // Keep the history consistent: no unanswered questions.
            $question->delete();

            throw $e;
        }

        if ($conversation->messages()->count() <= 2 && Str::startsWith($conversation->title, __('New conversation'))) {
            $conversation->update(['title' => Str::limit($message, 60)]);
        }

        $conversation->touch();

        return $conversation->messages()->create([
            'role' => 'model',
            'content' => $result['text'],
            'tokens' => $result['tokens'],
        ]);
    }

    /** Executive analysis of the whole business. */
    public function analyzeBusiness(string $focus = 'general'): string
    {
        $focusText = match ($focus) {
            'churn' => 'Focus on churn risk: which signals indicate cancellations, which customers or plans are at risk and how to retain them.',
            'revenue' => 'Focus on revenue: MRR trend, collections, overdue invoices, cash-flow risks and growth levers.',
            'pricing' => 'Focus on pricing and plans: which plans perform best, pricing opportunities, upsell and cross-sell ideas.',
            'sellers' => 'Focus on the sales team: seller performance, commissions and recommendations to improve sales.',
            default => 'Give a complete executive overview of the health of the business.',
        };

        $prompt = "Analyse the following subscription business metrics (JSON).\n{$focusText}\n"
            ."Answer in Markdown with these sections: an executive summary, key findings (with numbers), risks, opportunities and 5 concrete prioritised recommendations.\n\n"
            .json_encode($this->metrics->aiContext(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return $this->generate([['role' => 'user', 'text' => $prompt]], $this->analystInstruction(), 0.4)['text'];
    }

    /** Churn-risk and upsell analysis for one customer. */
    public function analyzeCustomer(Customer $customer): string
    {
        $customer->loadMissing('subscriptions.plan.product', 'invoices', 'payments', 'currency');

        $data = [
            'customer' => $customer->only(['code', 'name', 'company', 'status', 'country', 'created_at']),
            'subscriptions' => $customer->subscriptions->map(fn ($s) => [
                'reference' => $s->reference,
                'plan' => $s->plan?->fullName(),
                'status' => $s->status,
                'amount_per_period' => $s->periodAmount(),
                'cycle' => $s->plan?->billing_cycle,
                'start_date' => $s->start_date?->toDateString(),
                'next_billing_date' => $s->next_billing_date?->toDateString(),
                'auto_renew' => $s->auto_renew,
                'cancel_reason' => $s->cancel_reason,
            ])->values(),
            'invoices' => $customer->invoices->sortByDesc('issue_date')->take(24)->map(fn ($i) => [
                'number' => $i->number,
                'issue_date' => $i->issue_date?->toDateString(),
                'due_date' => $i->due_date?->toDateString(),
                'status' => $i->status,
                'total' => (float) $i->total,
                'paid' => (float) $i->amount_paid,
                'paid_at' => $i->paid_at?->toDateString(),
            ])->values(),
            'outstanding_balance' => $customer->outstandingBalance(),
            'currency' => $customer->currency?->code ?? base_currency()?->code,
            'today' => today()->toDateString(),
        ];

        $prompt = "Evaluate this customer of a subscription business. Return Markdown with: a churn-risk score from 0 to 100 with justification, payment behaviour, upsell/cross-sell opportunities and 3 recommended next actions.\n\n"
            .json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return $this->generate([['role' => 'user', 'text' => $prompt]], $this->analystInstruction(), 0.4)['text'];
    }

    /** Draft an e-mail template body. */
    public function draftEmail(string $purpose): string
    {
        $prompt = "Write a short, friendly and professional transactional e-mail for a subscription company. Purpose: {$purpose}.\n"
            .'You may use these placeholders literally: '.implode(' ', \App\Models\EmailTemplate::placeholders())."\n"
            .'Return only the plain-text body, no subject line, no Markdown.';

        return $this->generate([['role' => 'user', 'text' => $prompt]], $this->languageInstruction(), 0.7)['text'];
    }

    protected function assistantInstruction(): string
    {
        $company = setting('company_name', config('app.name'));
        $context = json_encode($this->metrics->aiContext(), JSON_UNESCAPED_UNICODE);

        return "You are the AI assistant built into \"{$company}\", a subscription management ERP. "
            .'You help the team with billing, renewals, customers, invoices, payments, pricing, retention and how to use the system '
            .'(modules: dashboard, customers, subscriptions, plans and products, invoices, payments, sellers, suppliers, Kanban, calendar, reports, settings). '
            .'Be concise, practical and use Markdown (lists and tables) when useful. Never invent data: when a number is not in the context say so. '
            .$this->languageInstruction()
            ."\n\nCurrent business snapshot (JSON, amounts in the base currency):\n{$context}";
    }

    protected function analystInstruction(): string
    {
        return 'You are a senior SaaS/subscription business analyst. Base every statement on the data provided, quote figures, '
            .'and keep recommendations specific and actionable. '.$this->languageInstruction();
    }

    protected function languageInstruction(): string
    {
        $language = config('app.available_locales')[app()->getLocale()] ?? 'Español';

        return "Always answer in {$language}.";
    }
}
