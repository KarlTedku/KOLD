<?php

namespace App\Http\Controllers;

use App\Models\DataDeletionRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DataDeletionController extends Controller
{
    public function instructions(): View
    {
        return view('legal.data-deletion');
    }

    public function account(Request $request): View
    {
        return view('account.delete', ['user' => $request->user()]);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'confirmation' => ['required', 'in:DELETE'],
        ], [
            'confirmation.in' => '請輸入 DELETE 確認刪除帳戶。',
        ]);

        $user = $request->user();
        $identifier = $user->provider.':'.$user->provider_id.':'.$user->id;
        $confirmationCode = $this->confirmationCode();

        Auth::logout();

        DB::transaction(function () use ($user, $identifier, $confirmationCode): void {
            DataDeletionRequest::query()->create([
                'confirmation_code' => $confirmationCode,
                'source' => 'account',
                'identifier_hash' => hash('sha256', $identifier),
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            $user->delete();
        });

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('data-deletion.status', $confirmationCode);
    }

    public function meta(Request $request): JsonResponse
    {
        $signedRequest = (string) $request->input('signed_request');
        $payload = $this->parseSignedRequest($signedRequest);

        abort_unless(is_array($payload) && filled($payload['user_id'] ?? null), 400, 'Invalid signed request.');

        $providerId = (string) $payload['user_id'];
        $confirmationCode = $this->confirmationCode();

        DB::transaction(function () use ($providerId, $confirmationCode): void {
            DataDeletionRequest::query()->create([
                'confirmation_code' => $confirmationCode,
                'source' => 'meta',
                'identifier_hash' => hash('sha256', 'facebook:'.$providerId),
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            User::query()
                ->where('provider', 'facebook')
                ->where('provider_id', $providerId)
                ->eachById(fn (User $user) => $user->delete());
        });

        return response()->json([
            'url' => route('data-deletion.status', $confirmationCode),
            'confirmation_code' => $confirmationCode,
        ]);
    }

    public function status(string $code): View
    {
        $deletionRequest = DataDeletionRequest::query()
            ->where('confirmation_code', $code)
            ->firstOrFail();

        return view('legal.data-deletion-status', compact('deletionRequest'));
    }

    /** @return array<string, mixed>|null */
    protected function parseSignedRequest(string $signedRequest): ?array
    {
        $secret = (string) config('services.facebook.client_secret');

        if ($signedRequest === '' || $secret === '' || ! str_contains($signedRequest, '.')) {
            return null;
        }

        [$encodedSignature, $encodedPayload] = explode('.', $signedRequest, 2);
        $signature = $this->base64UrlDecode($encodedSignature);
        $payloadJson = $this->base64UrlDecode($encodedPayload);

        if ($signature === false || $payloadJson === false) {
            return null;
        }

        $expected = hash_hmac('sha256', $encodedPayload, $secret, true);

        if (! hash_equals($expected, $signature)) {
            return null;
        }

        $payload = json_decode($payloadJson, true);

        return is_array($payload) && ($payload['algorithm'] ?? null) === 'HMAC-SHA256'
            ? $payload
            : null;
    }

    protected function base64UrlDecode(string $value): string|false
    {
        $padding = (4 - strlen($value) % 4) % 4;

        return base64_decode(strtr($value, '-_', '+/').str_repeat('=', $padding), true);
    }

    protected function confirmationCode(): string
    {
        do {
            $code = 'KOLD-'.Str::upper(Str::random(16));
        } while (DataDeletionRequest::query()->where('confirmation_code', $code)->exists());

        return $code;
    }
}
