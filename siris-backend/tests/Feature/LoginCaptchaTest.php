<?php

namespace Tests\Feature;

use App\Services\LoginCaptcha;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

class LoginCaptchaTest extends TestCase
{
    public function test_challenge_returns_png_without_answer(): void
    {
        $response = $this->getJson('/api/captcha')->assertOk();
        $data = $response->json('data');
        $this->assertTrue(Str::isUuid($data['id']));
        $this->assertSame(300, $data['expires_in']);
        $this->assertArrayNotHasKey('answer', $data);
        $this->assertStringStartsWith('data:image/png;base64,', $data['image']);
        $this->assertSame("\x89PNG\r\n\x1a\n", substr(base64_decode(explode(',', $data['image'])[1]), 0, 8));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_login_requires_captcha(): void
    {
        $this->postJson('/api/login', ['email' => 'admin@example.test', 'password' => 'password'])
            ->assertUnprocessable()->assertJsonValidationErrors(['captcha_id', 'captcha_answer']);
    }

    public function test_wrong_captcha_blocks_login_and_consumes_challenge(): void
    {
        $id = $this->seedChallenge();
        $this->postJson('/api/login', [
            'email' => 'admin@example.test', 'password' => 'password',
            'captcha_id' => $id, 'captcha_answer' => 'WRONG2',
        ])->assertUnprocessable()->assertJsonPath('success', false);
        $this->assertFalse(app(LoginCaptcha::class)->verify($id, 'ABC234'));
    }

    public function test_correct_answer_is_case_insensitive_and_single_use(): void
    {
        $id = $this->seedChallenge();
        $this->assertTrue(app(LoginCaptcha::class)->verify($id, 'abc234'));
        $this->assertFalse(app(LoginCaptcha::class)->verify($id, 'ABC234'));
    }

    public function test_expired_challenge_is_rejected(): void
    {
        $id = $this->seedChallenge();
        $this->travel(6)->minutes();
        $this->assertFalse(app(LoginCaptcha::class)->verify($id, 'ABC234'));
        $this->travelBack();
    }

    public function test_unknown_challenge_is_rejected(): void
    {
        $this->assertFalse(app(LoginCaptcha::class)->verify((string) Str::uuid(), 'ABC234'));
    }

    private function seedChallenge(): string
    {
        $id = (string) Str::uuid();
        Cache::put('login-captcha:'.$id, hash('sha256', 'ABC234'), now()->addMinutes(5));

        return $id;
    }
}
