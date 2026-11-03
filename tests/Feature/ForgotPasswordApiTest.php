<?php

namespace Tests\Feature;

use App\Mail\PasswordResetOtp;
use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ForgotPasswordApiTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
        (require database_path('migrations/2026_05_11_000000_create_api_tokens_table.php'))->up();
        (require database_path('migrations/2026_11_03_000001_create_password_reset_otps_table.php'))->up();
        Mail::fake();
        $this->user = User::create(['name' => 'Customer', 'email' => 'customer@example.com', 'password' => 'OldPassword1!']);
    }

    private function sendCode(): string
    {
        $this->postJson('/api/forgot-password/send-otp', ['email' => $this->user->email])->assertOk()->assertJsonMissingPath('otp');

        return Mail::sent(PasswordResetOtp::class)->last()->otp;
    }

    private function verify(string $otp): string
    {
        return $this->postJson('/api/forgot-password/verify-otp', ['email' => $this->user->email, 'otp' => $otp])
            ->assertOk()->assertHeader('Cache-Control', 'no-store, private')->json('reset_token');
    }

    private function resetData(string $token): array
    {
        return ['email' => $this->user->email, 'reset_token' => $token, 'password' => 'NewPassword2!', 'password_confirmation' => 'NewPassword2!'];
    }

    public function test_customer_can_verify_code_and_reset_password_only_once(): void
    {
        ApiToken::create(['user_id' => $this->user->id, 'name' => 'api', 'token' => hash('sha256', 'old-token')]);
        $otp = $this->sendCode();
        $stored = DB::table('password_reset_otps')->first();
        $this->assertNotSame($otp, $stored->otp_hash);
        $this->assertTrue(Hash::check($otp, $stored->otp_hash));
        Mail::assertSent(PasswordResetOtp::class, fn ($mail) => $mail->hasTo($this->user->email));
        $this->assertStringContainsString($otp, (new PasswordResetOtp($otp))->render());
        $token = $this->verify($otp);
        $this->assertSame(hash('sha256', $token), DB::table('password_reset_otps')->value('reset_token_hash'));
        $this->postJson('/api/forgot-password/verify-otp', ['email' => $this->user->email, 'otp' => $otp])->assertUnprocessable();
        $this->postJson('/api/forgot-password/reset-password', $this->resetData($token))->assertOk();
        $this->assertTrue(Hash::check('NewPassword2!', $this->user->fresh()->password));
        $this->assertFalse(Hash::check('OldPassword1!', $this->user->fresh()->password));
        $this->assertSame(0, ApiToken::count());
        $this->assertSame(0, DB::table('password_reset_otps')->count());
        $this->postJson('/api/forgot-password/reset-password', $this->resetData($token))->assertUnprocessable();
    }

    public function test_unknown_and_deleted_accounts_receive_generic_response_without_email(): void
    {
        $unknown = $this->postJson('/api/forgot-password/send-otp', ['email' => 'missing@example.com'])->assertOk()->json();
        $this->user->delete();
        $deleted = $this->postJson('/api/forgot-password/send-otp', ['email' => $this->user->email])->assertOk()->json();
        $this->assertSame($unknown, $deleted);
        Mail::assertNothingSent();
        $this->assertSame(0, DB::table('password_reset_otps')->count());
    }

    public function test_code_expires_and_five_wrong_attempts_lock_it(): void
    {
        $otp = $this->sendCode();
        $wrong = $otp === '111111' ? '222222' : '111111';
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/forgot-password/verify-otp', ['email' => $this->user->email, 'otp' => $wrong])->assertUnprocessable();
        }
        $this->postJson('/api/forgot-password/verify-otp', ['email' => $this->user->email, 'otp' => $otp])->assertUnprocessable();
        $this->assertSame(5, DB::table('password_reset_otps')->value('attempts'));
        $this->travel(61)->seconds();
        $newOtp = $this->sendCode();
        $this->travel(10)->minutes();
        $this->postJson('/api/forgot-password/verify-otp', ['email' => $this->user->email, 'otp' => $newOtp])->assertUnprocessable();
    }

    public function test_resend_cooldown_and_resend_invalidates_verified_token(): void
    {
        $token = $this->verify($this->sendCode());
        $this->postJson('/api/forgot-password/send-otp', ['email' => strtoupper($this->user->email)])->assertStatus(429);
        $this->travel(61)->seconds();
        $this->sendCode();
        $this->postJson('/api/forgot-password/reset-password', $this->resetData($token))->assertUnprocessable();
        $this->assertSame(0, DB::table('password_reset_otps')->value('attempts'));
    }

    public function test_reset_requires_verified_unexpired_email_bound_token_and_strong_confirmed_password(): void
    {
        $this->sendCode();
        $this->postJson('/api/forgot-password/reset-password', $this->resetData(str_repeat('a', 64)))->assertUnprocessable();
        $otp = Mail::sent(PasswordResetOtp::class)->last()->otp;
        $token = $this->verify($otp);
        $weak = $this->resetData($token);
        $weak['password'] = $weak['password_confirmation'] = 'weak';
        $this->postJson('/api/forgot-password/reset-password', $weak)->assertUnprocessable()->assertJsonValidationErrors('password');
        $mismatch = $this->resetData($token);
        $mismatch['password_confirmation'] = 'SomethingElse1!';
        $this->postJson('/api/forgot-password/reset-password', $mismatch)->assertUnprocessable()->assertJsonValidationErrors('password');
        $other = $this->resetData($token);
        $other['email'] = 'other@example.com';
        $this->postJson('/api/forgot-password/reset-password', $other)->assertUnprocessable();
        $this->travel(10)->minutes();
        $this->postJson('/api/forgot-password/reset-password', $this->resetData($token))->assertUnprocessable();
        $this->assertTrue(Hash::check('OldPassword1!', $this->user->fresh()->password));
    }

    public function test_mail_failure_rolls_back_new_code(): void
    {
        Mail::shouldReceive('to')->once()->andThrow(new \RuntimeException('Mail unavailable'));
        $this->postJson('/api/forgot-password/send-otp', ['email' => $this->user->email])->assertStatus(503);
        $this->assertSame(0, DB::table('password_reset_otps')->count());
    }

    public function test_reset_removes_database_sessions_only_for_the_reset_customer(): void
    {
        Schema::create('sessions', function ($table) {
            $table->string('id')->primary();
            $table->unsignedBigInteger('user_id')->nullable();
        });
        DB::table('sessions')->insert([
            ['id' => 'customer-session', 'user_id' => $this->user->id],
            ['id' => 'other-session', 'user_id' => $this->user->id + 1],
        ]);
        $token = $this->verify($this->sendCode());
        config(['session.driver' => 'database', 'session.table' => 'sessions']);
        $this->postJson('/api/forgot-password/reset-password', $this->resetData($token))->assertOk();
        $this->assertSame(['other-session'], DB::table('sessions')->pluck('id')->all());
    }
}
