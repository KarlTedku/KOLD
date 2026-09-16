<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UserAvatarService
{
    public function setManual(User $user, UploadedFile $file): string
    {
        $oldPath = $this->manualAvatarPath($user);
        $path = $file->store('avatars', 'public');

        if (! $path) {
            throw new \RuntimeException('頭像上載失敗，請再試一次。');
        }

        $user->forceFill([
            'avatar' => Storage::disk('public')->url($path),
            'avatar_source' => User::AVATAR_SOURCE_MANUAL,
        ])->save();

        if ($oldPath && $oldPath !== $path) {
            Storage::disk('public')->delete($oldPath);
        }

        return $path;
    }

    public function setFromMeta(User $user, ?string $url, bool $force = false): bool
    {
        if (blank($url) || (! $force && $user->avatar_source === User::AVATAR_SOURCE_MANUAL)) {
            return false;
        }

        $oldPath = $this->manualAvatarPath($user);

        $user->forceFill([
            'avatar' => $url,
            'avatar_source' => User::AVATAR_SOURCE_META,
        ])->save();

        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        return true;
    }

    public function setFromOAuth(User $user, ?string $url): bool
    {
        if (blank($url) || in_array($user->avatar_source, [
            User::AVATAR_SOURCE_MANUAL,
            User::AVATAR_SOURCE_META,
        ], true)) {
            return false;
        }

        $user->forceFill([
            'avatar' => $url,
            'avatar_source' => User::AVATAR_SOURCE_OAUTH,
        ]);

        return true;
    }

    protected function manualAvatarPath(User $user): ?string
    {
        if ($user->avatar_source !== User::AVATAR_SOURCE_MANUAL || blank($user->avatar)) {
            return null;
        }

        $urlPath = parse_url((string) $user->avatar, PHP_URL_PATH);
        if (! is_string($urlPath)) {
            return null;
        }

        $marker = '/storage/avatars/';
        $position = strpos($urlPath, $marker);
        if ($position === false) {
            return null;
        }

        return ltrim(substr($urlPath, $position + strlen('/storage/')), '/');
    }
}
