<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class UpdateUserProfileController extends Controller
{
    public function __invoke(UpdateProfileRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();
        unset($data['foto']);

        if ($request->hasFile('foto')) {
            $this->deleteStoredAvatarIfOwned($user);
            $path = $request->file('foto')->store('users/avatars', 'public');
            $data['foto_url'] = $request->getSchemeAndHttpHost().'/storage/'.$path;
        }

        if ($data !== []) {
            $user->update($data);
        }

        $user->refresh();

        return app(UserController::class)($request);
    }

    private function deleteStoredAvatarIfOwned(User $user): void
    {
        $raw = $user->getAttributes()['foto_url'] ?? null;
        if (! is_string($raw) || $raw === '') {
            return;
        }
        $path = parse_url($raw, PHP_URL_PATH);
        if (! is_string($path) || ! str_starts_with($path, '/storage/users/avatars/')) {
            return;
        }
        $relative = ltrim(str_replace('/storage/', '', $path), '/');
        Storage::disk('public')->delete($relative);
    }
}
