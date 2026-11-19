<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Leaderboard;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * POST /api/v1/dev-login
     * ΜΟΝΟ ΓΙΑ ΔΟΚΙΜΕΣ (appenv=local)
     * Δεν συνδέεται με το apps καθόλου.
     */
    public function devLogin(Request $request): JsonResponse
    {
        if (!app()->environment('local')) {
            return response()->json([
                'message' => 'Dev login is disabled outside local environment.'], 403);
        }

        $validated = $request->validate([
            'username' => ['required', 'string', 'min:2', 'max:50', 'alpha_dash']]);

        $username = strtolower($validated['username']);

        $user = $this->upsertUserWithToken(
            ieeId: 'dev_' . $username,
            username: $username,
            fullName: 'Dev Player (' . $username . ')'
        );

        return response()->json([
            'token' => $user->api_token, //για postman testing. delete before deployment
            'user'  => $user->load('leaderboard'),
        ]);
    }

    /**
     * GET /api/v1/apps-login
     * πάει προς το apps για authorization
     */
    public function redirectToIee(): RedirectResponse
    {
        $clientId    = config('services.iee.client_id');
        $redirectUri = config('services.iee.redirect_uri');
        $scopes      = config('services.iee.scopes', 'profile');

        if (!$clientId) {
            $frontendUrl = rtrim(env('FRONTEND_URL', 'http://localhost:5173'), '/'); //change before deployment
            return redirect()->away("{$frontendUrl}/?auth_error=missing_iee_config");
        }

        $query = http_build_query([
            'client_id'     => $clientId,
            'redirect_uri'  => $redirectUri,
            'response_type' => 'code',
            'scope'         => $scopes,
        ]);

        return redirect()->away("https://login.iee.ihu.gr/authorization?{$query}");
    }

    /**
     * GET /api/v1/callback
     * παίρνει το token απο το apps, και φτιάχνει token για το frontend.
     */
    public function handleIeeCallback(Request $request): RedirectResponse|JsonResponse
    {
        if ($request->has('error')) {
            return redirect()->away($this->frontendUrl('?auth_error=' . urlencode($request->query('error'))));
        }

        $code  = $request->query('code');
        $state = $request->query('state');

        if (!$code) {
            return redirect()->away($this->frontendUrl('?auth_error=missing_code'));
        }

        // Αν το IEE γυρίσει κανονικό state το ελέγχουμε, αν γυρίσει 'undefined' το προσπερνάμε
        if ($state && $state !== 'undefined' && !Cache::pull("oauth_state:{$state}")) {
            return redirect()->away($this->frontendUrl('?auth_error=invalid_state'));
        }

        $ieeUser = $this->fetchIeeProfile($code);

        if (!$ieeUser) {
            return redirect()->away($this->frontendUrl('?auth_error=iee_auth_failed'));
        }

        $user = $this->upsertUserWithToken($ieeUser['iee_id'], $ieeUser['username'], $ieeUser['full_name']);

        // Αν δεν τρέχει frontend, δείξε κατευθείαν το JSON αποτέλεσμα στον browser
        if (!env('FRONTEND_URL')) {
            return response()->json([
                'token' => $user->api_token,
                'user'  => $user->load('leaderboard'),
            ]);
        }

        return redirect()->away($this->frontendUrl('#token=' . urlencode($user->api_token)));
    }

    /**
     * GET /api/v1/profile
     * επιστροφή δεδομένων χρήστη + leaderboard stats
     */
    public function profile(Request $request): JsonResponse
    {
        return response()->json($request->user()->load('leaderboard'));
    }

    /**
     * POST /api/v1/logout
     * Διαγράφει το api_token του χρήστη, logout
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->update(['api_token' => null]);

        return response()->json(['message' => 'Logged out successfully.']);
    }

    //Methods for internal use

    //API Token Creator
    private function upsertUserWithToken(string $ieeId, string $username, string $fullName): User
    {
        return DB::transaction(function () use ($ieeId, $username, $fullName) {
            $token = Str::random(60);

            $user = User::updateOrCreate(
                ['iee_id' => $ieeId],
                [
                    'username'    => $username,
                    'full_name'   => $fullName,
                    'api_token'   => $token,
                    'last_active' => now(),
                ]
            );

            //Εννοιαιες εγγραφες στον πινακα
            Leaderboard::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'wins'   => 0,
                    'losses' => 0,
                    'draws'  => 0,
                    'elo'    => 1000,
                ]
            );

            //manual επιστροφη επειδη ειναι hidden
            $user->api_token = $token;

            return $user;
        });
    }

    private function fetchIeeProfile(string $code): ?array
    {
        $http = Http::when(app()->isLocal(), fn ($client) => $client->withoutVerifying());
        $tokenResponse = $http->asForm()->post('https://login.iee.ihu.gr/token', [
            'client_id'     => config('services.iee.client_id'),
            'client_secret' => config('services.iee.client_secret'),
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'redirect_uri'  => config('services.iee.redirect_uri'),
        ]);

        if (!$tokenResponse->successful()) {
            logger()->error('IEE token error', [
                'status' => $tokenResponse->status(),
                'body'   => $tokenResponse->body(),
            ]);
            return null;
        }

        $accessToken = $tokenResponse->json('access_token');
        if (!$accessToken) {
            return null;
        }

        $profileResponse = $http->withHeaders([
                'x-access-token' => $accessToken,
            ])
            ->acceptJson()
            ->get('https://api.iee.ihu.gr/profile');

        if (!$profileResponse->successful()) {
            logger()->error('IEE profile error', [
                'status' => $profileResponse->status(),
                'body'   => $profileResponse->body(),
            ]);
            return null;
        }

        $profile  = $profileResponse->json();
        $ieeId    = (string) ($profile['id'] ?? $profile['uid'] ?? $profile['am'] ?? '');
        $username = (string) ($profile['uid'] ?? $profile['username'] ?? "iee_{$ieeId}");
        $fullName = trim(($profile['givenName'] ?? $profile['first_name'] ?? '') . ' ' . ($profile['sn'] ?? $profile['last_name'] ?? ''));

        if ($fullName === '') {
            $fullName = $profile['cn'] ?? $profile['displayName'] ?? $username;
        }

        return $ieeId === '' ? null : [
            'iee_id'    => $ieeId,
            'username'  => Str::limit($username, 50, ''),
            'full_name' => Str::limit($fullName, 120, ''),
        ];
    }
    private function frontendUrl(string $suffix = ''): string
    {
        $base = rtrim((string) env('FRONTEND_URL', ''), '/');
        if ($base === '') {
            return url('/api/v1/profile') . $suffix;
        }
        return $base . '/' . ltrim($suffix, '/');
    }

}

