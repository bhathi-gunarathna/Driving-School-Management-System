<?php

namespace Tests\Feature;

use App\Models\User;
use App\Logging\RedactPasswordResetTokens;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_forgot_and_reset_pages_are_available(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Send Reset Link')
            ->assertSee('Back to Login');

        $user = User::factory()->create();
        $token = Password::broker()->createToken($user);

        $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))
            ->assertOk()
            ->assertSee('Reset Password')
            ->assertSee('Confirm new password')
            ->assertSee($user->email);
    }

    public function test_owner_and_coach_receive_reset_links_generated_from_app_url(): void
    {
        Notification::fake();
        config(['app.url' => 'https://school.example.test']);
        URL::forceRootUrl(config('app.url'));
        URL::forceScheme('https');

        foreach (['Owner', 'Coach'] as $roleName) {
            $user = User::factory()->create(['email' => strtolower($roleName).'@example.test']);
            $user->assignRole(Role::findOrCreate($roleName, 'web'));

            $response = $this->from(route('password.request'))
                ->post(route('password.email'), ['email' => $user->email]);

            $response->assertRedirect(route('password.request'))
                ->assertSessionHas('status', 'If an account exists for that email address, a password reset link will be sent.');

            $sentNotification = null;
            Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification, $channels) use (&$sentNotification) {
                $sentNotification = $notification;

                return in_array('mail', $channels, true);
            });

            $this->assertNotNull($sentNotification);
            $this->assertNotSame(
                $sentNotification->token,
                DB::table('password_reset_tokens')->where('email', $user->email)->value('token')
            );
            $this->assertStringStartsWith('https://school.example.test/reset-password/', $sentNotification->toMail($user)->actionUrl);
            $this->assertFalse(str_contains($response->getContent(), $sentNotification->token));
        }
    }

    public function test_unknown_email_gets_the_same_neutral_response_without_a_notification(): void
    {
        Notification::fake();

        $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => 'unknown@example.test'])
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('status', 'If an account exists for that email address, a password reset link will be sent.')
            ->assertSessionHasNoErrors();

        Notification::assertNothingSent();
    }

    public function test_reset_link_requests_keep_the_configured_expiry_and_throttle(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHas('status', 'If an account exists for that email address, a password reset link will be sent.');

        $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHas('status', 'If an account exists for that email address, a password reset link will be sent.');

        Notification::assertSentToTimes($user, ResetPasswordNotification::class, 1);
        $this->assertSame(60, config('auth.passwords.users.expire'));
        $this->assertSame(60, config('auth.passwords.users.throttle'));
    }

    public function test_reset_tokens_are_redacted_from_log_mail_content(): void
    {
        $token = str_repeat('a', 64);
        $loggedMessage = 'Reset link: https://school.example.test/reset-password/'.$token.'?email=owner%40example.test';

        $redacted = RedactPasswordResetTokens::redact($loggedMessage);

        $this->assertStringNotContainsString($token, $redacted);
        $this->assertStringContainsString('/reset-password/[REDACTED]?email=', $redacted);
        $this->assertSame('password_reset_mail', config('mail.mailers.log.channel'));
    }

    public function test_valid_token_resets_owner_password_without_changing_role(): void
    {
        $user = User::factory()->create(['password' => Hash::make('Current-pass-123')]);
        $user->assignRole(Role::findOrCreate('Owner', 'web'));
        $oldHash = $user->password;
        $token = Password::broker()->createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'Updated-pass-456',
            'password_confirmation' => 'Updated-pass-456',
        ])->assertRedirect(route('login'))
            ->assertSessionHas('status');

        $user->refresh();
        $this->assertNotSame($oldHash, $user->password);
        $this->assertTrue(Hash::check('Updated-pass-456', $user->password));
        $this->assertTrue($user->hasRole('Owner'));
        $this->assertGuest();
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
    }

    public function test_valid_token_resets_coach_password_without_changing_role(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('Coach', 'web'));
        $token = Password::broker()->createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'Updated-pass-456',
            'password_confirmation' => 'Updated-pass-456',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('Updated-pass-456', $user->fresh()->password));
        $this->assertTrue($user->fresh()->hasRole('Coach'));
    }

    public function test_invalid_token_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->from(route('password.reset', ['token' => 'invalid-token', 'email' => $user->email]))
            ->post(route('password.update'), [
                'token' => 'invalid-token',
                'email' => $user->email,
                'password' => 'Updated-pass-456',
                'password_confirmation' => 'Updated-pass-456',
            ])
            ->assertRedirect(route('password.reset', ['token' => 'invalid-token', 'email' => $user->email]))
            ->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_expired_token_is_rejected(): void
    {
        $user = User::factory()->create();
        $token = Password::broker()->createToken($user);
        DB::table('password_reset_tokens')
            ->where('email', $user->email)
            ->update(['created_at' => now()->subMinutes(config('auth.passwords.users.expire') + 1)]);

        $this->from(route('password.reset', ['token' => $token, 'email' => $user->email]))
            ->post(route('password.update'), [
                'token' => $token,
                'email' => $user->email,
                'password' => 'Updated-pass-456',
                'password_confirmation' => 'Updated-pass-456',
            ])
            ->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_password_confirmation_is_required_to_match(): void
    {
        $user = User::factory()->create();
        $token = Password::broker()->createToken($user);

        $this->from(route('password.reset', ['token' => $token, 'email' => $user->email]))
            ->post(route('password.update'), [
                'token' => $token,
                'email' => $user->email,
                'password' => 'Updated-pass-456',
                'password_confirmation' => 'Different-pass-789',
            ])
            ->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_existing_login_flow_still_authenticates_owner(): void
    {
        $user = User::factory()->create([
            'email' => 'owner@example.test',
            'password' => Hash::make('Current-pass-123'),
            'is_active' => true,
        ]);
        $user->assignRole(Role::findOrCreate('Owner', 'web'));

        $this->post(route('login'), [
            'email' => 'owner@example.test',
            'password' => 'Current-pass-123',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($user);
    }
}
