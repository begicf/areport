<?php

namespace Tests\Feature;

use App\User;
use Begicf\AReportPro\Models\DpmLanguage;
use Begicf\AReportPro\Models\DpmSetting;
use Begicf\AReportPro\Services\AiTranslator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiTranslationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->requirePro();
        config(['areport-pro.ai.api_key' => null, 'areport-pro.ai.openai.api_key' => null]);
        $this->actingAs(User::query()->create(['name' => 'Alice', 'email' => 'alice@example.com', 'password' => Hash::make('secret')]));
        DpmLanguage::syncFromConfig();
    }

    public function test_translation_is_off_without_a_key(): void
    {
        $this->assertNull(AiTranslator::info());

        $this->postJson('/pro/ai/translate', ['texts' => ['1' => 'Own funds'], 'to' => 'bs'])
            ->assertStatus(422)
            ->assertJson(['ok' => false]);
    }

    public function test_the_key_is_stored_encrypted_and_can_be_removed(): void
    {
        $this->post('/pro/settings/ai', ['ai_api_key' => 'sk-ant-secret-1234', 'ai_model' => 'claude-sonnet-5-5', 'ai_enabled' => '1'])
            ->assertRedirect();

        $this->assertNotSame('sk-ant-secret-1234', DpmSetting::get('ai_api_key'));
        $this->assertSame('sk-ant-secret-1234', AiTranslator::apiKey());
        $this->assertSame(['provider' => 'claude', 'model' => 'claude-sonnet-5-5', 'label' => AiTranslator::MODELS['claude-sonnet-5-5']], AiTranslator::info());

        // A blank key keeps the saved one; "remove" clears it.
        $this->post('/pro/settings/ai', ['ai_api_key' => '', 'ai_model' => 'claude-opus-5-5', 'ai_enabled' => '1']);
        $this->assertSame('sk-ant-secret-1234', AiTranslator::apiKey());
        $this->post('/pro/settings/ai', ['ai_model' => 'claude-opus-5-5', 'ai_forget' => '1', 'ai_enabled' => '1']);
        $this->assertNull(AiTranslator::apiKey());
    }

    public function test_unknown_target_language_is_rejected(): void
    {
        $this->postJson('/pro/ai/translate', ['texts' => ['1' => 'Own funds'], 'to' => 'xx'])
            ->assertStatus(422);
    }

    public function test_openai_can_be_chosen_with_its_own_key_and_model(): void
    {
        $this->post('/pro/settings/ai', ['ai_provider' => 'openai', 'ai_openai_api_key' => 'sk-openai-secret-9876',
            'ai_openai_model' => 'gpt-5-mini', 'ai_model' => 'claude-opus-5-5', 'ai_enabled' => '1'])->assertRedirect();

        $this->assertNotSame('sk-openai-secret-9876', DpmSetting::get('ai_openai_api_key'));
        $this->assertSame('sk-openai-secret-9876', AiTranslator::apiKey());
        $this->assertNull(AiTranslator::apiKey('claude'));
        $this->assertSame(['provider' => 'openai', 'model' => 'gpt-5-mini', 'label' => 'OpenAI gpt-5-mini'], AiTranslator::info());
    }

    public function test_openai_translations_come_back_as_suggestions(): void
    {
        DpmSetting::put('ai_provider', 'openai');
        DpmSetting::put('ai_openai_api_key', \Illuminate\Support\Facades\Crypt::encryptString('sk-openai-test'));
        Http::fake(['api.openai.com/*' => Http::response(['choices' => [['message' => ['role' => 'assistant',
            'content' => json_encode(['translations' => [['id' => '1', 'text' => 'Regulatorni kapital'], ['id' => '9', 'text' => 'unknown id']]])]]]])]);

        $this->postJson('/pro/ai/translate', ['texts' => ['1' => 'Own funds'], 'to' => 'bs'])
            ->assertOk()
            ->assertJson(['ok' => true, 'model' => 'gpt-5-mini', 'translations' => ['1' => 'Regulatorni kapital']])
            ->assertJsonMissingPath('translations.9');

        Http::assertSent(fn ($r) => $r->hasHeader('Authorization', 'Bearer sk-openai-test')
            && $r['model'] === 'gpt-5-mini' && $r['response_format']['type'] === 'json_schema');
    }

    public function test_a_rejected_openai_key_is_reported(): void
    {
        DpmSetting::put('ai_provider', 'openai');
        DpmSetting::put('ai_openai_api_key', \Illuminate\Support\Facades\Crypt::encryptString('sk-wrong'));
        Http::fake(['api.openai.com/*' => Http::response(['error' => ['message' => 'Incorrect API key']], 401)]);

        $this->postJson('/pro/ai/translate', ['texts' => ['1' => 'Own funds'], 'to' => 'bs'])
            ->assertStatus(502)
            ->assertJson(['ok' => false, 'error' => 'OpenAI rejected the API key — check it under Settings › AI translation.']);
    }
}
