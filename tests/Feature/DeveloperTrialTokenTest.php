<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\TestResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class DeveloperTrialTokenTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('SQLite PDO driver is required for this isolated feature test.');
        }

        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('users');

        Schema::create('users', function ($table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('password');
            $table->string('role');
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('personal_access_tokens', function ($table): void {
            $table->id();
            $table->morphs('tokenable');
            $table->text('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function test_local_developer_api_login_issues_full_access_trial_token(): void
    {
        config(['app.env' => 'local']);
        $this->createDeveloper();

        $token = $this->login()->json('token');
        $record = PersonalAccessToken::findToken($token);

        $this->assertNotNull($record);
        $this->assertSame(['*'], $record->abilities);

        $this->withToken($token)
            ->postJson('/api/logout')
            ->assertOk();
    }

    public function test_production_developer_api_login_issues_get_only_trial_token(): void
    {
        config(['app.env' => 'production']);
        $this->createDeveloper();

        $token = $this->login()->json('token');
        $record = PersonalAccessToken::findToken($token);

        $this->assertNotNull($record);
        $this->assertSame(['docs:read'], $record->abilities);

        $this->withToken($token)
            ->postJson('/api/logout')
            ->assertForbidden()
            ->assertJson([
                'message' => 'Token trial tidak memiliki izin untuk request ini.',
            ]);
    }

    private function createDeveloper(): User
    {
        return User::query()->create([
            'name' => 'developer-test',
            'email' => 'developer-test@example.com',
            'password' => Hash::make('password'),
            'role' => 'developer',
        ]);
    }

    private function login(): TestResponse
    {
        return $this->postJson('/api/login', [
            'name' => 'developer-test',
            'password' => 'password',
        ])->assertOk();
    }
}
