<?php

namespace Tests\Feature;

use App\Exceptions\GeminiException;
use App\Models\AiConversation;
use App\Models\Setting;
use App\Models\User;
use App\Services\GeminiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\BuildsBillingData;
use Tests\TestCase;

class GeminiAssistantTest extends TestCase
{
    use BuildsBillingData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBaseData();
        Setting::set(['gemini_api_key' => 'test-key', 'gemini_model' => 'gemini-2.5-flash']);
    }

    protected function fakeGemini(string $text = 'Your **MRR** is healthy.'): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['role' => 'model', 'parts' => [['text' => $text]]]]],
                'usageMetadata' => ['totalTokenCount' => 42],
            ]),
        ]);
    }

    public function test_api_key_is_stored_encrypted(): void
    {
        $raw = Setting::query()->where('key', 'gemini_api_key')->value('value');

        $this->assertNotSame('test-key', $raw);
        $this->assertSame('test-key', Setting::get('gemini_api_key'));
    }

    public function test_chat_sends_history_and_business_context(): void
    {
        $this->fakeGemini();
        $admin = $this->admin();
        $conversation = $admin->aiConversations()->create(['title' => 'New conversation']);

        $response = $this->actingAs($admin)->postJson(route('ai.conversations.message', $conversation), [
            'message' => 'How is my MRR?',
        ]);

        $response->assertOk()->assertJsonPath('title', 'How is my MRR?');
        $this->assertStringContainsString('<strong>MRR</strong>', $response->json('html'));
        $this->assertSame(['user', 'model'], $conversation->messages()->pluck('role')->all());
        $this->assertSame(42, $conversation->messages()->where('role', 'model')->value('tokens'));

        Http::assertSent(function (Request $request) {
            return str_contains($request->url(), 'models/gemini-2.5-flash:generateContent')
                && $request->hasHeader('x-goog-api-key', 'test-key')
                && $request['contents'][0]['parts'][0]['text'] === 'How is my MRR?'
                && str_contains($request['systemInstruction']['parts'][0]['text'], '"kpis"');
        });
    }

    public function test_errors_are_reported_and_the_question_is_not_kept(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => ['message' => 'API key not valid']], 400)]);
        $admin = $this->admin();
        $conversation = $admin->aiConversations()->create(['title' => 'Test']);

        $this->actingAs($admin)
            ->postJson(route('ai.conversations.message', $conversation), ['message' => 'Hello'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Gemini error: API key not valid');

        $this->assertSame(0, $conversation->messages()->count());
    }

    public function test_service_requires_an_api_key(): void
    {
        Setting::set('gemini_api_key', null);
        config(['services.gemini.key' => null]);

        $this->expectException(GeminiException::class);

        app(GeminiService::class)->generate([['role' => 'user', 'text' => 'Hi']]);
    }

    public function test_business_insights_are_generated_and_cached(): void
    {
        $this->fakeGemini("## Summary\n\nAll good.");
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('ai.insights.generate'), ['focus' => 'churn'])->assertRedirect();

        $this->actingAs($admin)->get(route('ai.insights'))->assertOk()->assertSee('All good.');
        Http::assertSent(fn (Request $request) => str_contains($request['contents'][0]['parts'][0]['text'], 'churn risk'));
    }

    public function test_customer_analysis_is_shown_on_the_customer_page(): void
    {
        $this->fakeGemini('Churn risk: **20/100**');
        $admin = $this->admin();
        $customer = $this->makeCustomer();

        $this->actingAs($admin)
            ->from(route('customers.show', $customer))
            ->post(route('customers.ai-analysis', $customer))
            ->assertRedirect(route('customers.show', $customer))
            ->assertSessionHas('ai_analysis', 'Churn risk: **20/100**');
    }

    public function test_conversations_are_private(): void
    {
        $owner = $this->admin();
        $other = $this->admin();
        $conversation = AiConversation::query()->create(['user_id' => $owner->id, 'title' => 'Private']);

        $this->actingAs($other)->get(route('ai.conversations.show', $conversation))->assertForbidden();
    }

    public function test_sellers_cannot_use_the_assistant(): void
    {
        $seller = User::factory()->seller()->create();

        $this->actingAs($seller)->get(route('ai.index'))->assertForbidden();
    }
}
