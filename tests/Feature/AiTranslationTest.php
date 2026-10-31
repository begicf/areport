<?php

namespace Tests\Feature;

use App\User;
use Begicf\AReportPro\Models\DpmLanguage;
use Begicf\AReportPro\Models\DpmSetting;
use Begicf\AReportPro\Services\AiTranslator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AiTranslationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! class_exists(AiTranslator::class)) {
            $this->markTestSkipped('areport-pro is not installed.');
        }
        config(['areport-pro.ai.api_key' => null]);
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
        $this->assertSame(['model' => 'claude-sonnet-5-5', 'label' => AiTranslator::MODELS['claude-sonnet-5-5']], AiTranslator::info());

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
}
