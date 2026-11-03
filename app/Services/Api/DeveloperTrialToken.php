<?php

namespace App\Services\Api;

use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Sanctum\NewAccessToken;

class DeveloperTrialToken
{
    public static function issue(Request $request): string
    {
        $user = $request->user();

        abort_unless(
            $user && $user->role === 'developer',
            403,
            'Akses hanya untuk developer.'
        );

        // Hapus hanya token trial sesi sebelumnya.
        $oldId = $request->session()->pull('developer_trial_token_id');

        if ($oldId) {
            $user->tokens()
                ->whereKey($oldId)
                ->where('name', 'like', 'docs-trial-%')
                ->delete();
        }

        $created = self::create($user);

        $request->session()->put([
            'developer_trial_token' => $created->plainTextToken,
            'developer_trial_token_id' => $created->accessToken->id,
            'developer_trial_environment' => self::environment(),
        ]);

        return $created->plainTextToken;
    }

    /**
     * Issue the same restricted token for developer logins made through the API.
     */
    public static function issueForApi(User $user): string
    {
        abort_unless(
            $user->role === 'developer',
            403,
            'Akses hanya untuk developer.'
        );

        return self::create($user)->plainTextToken;
    }

    private static function create(User $user): NewAccessToken
    {
        $environment = self::environment();
        $abilities = $environment === 'local'
            ? ['*']
            : ['docs:read'];

        return $user->createToken(
            'docs-trial-' . $environment,
            $abilities,
            now()->addHours(8)
        );
    }

    private static function environment(): string
    {
        return app()->environment('local')
            ? 'local'
            : 'production';
    }
}
