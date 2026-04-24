<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user()->load(['roles', 'club']);

        return response()->json([
            'user' => $user,
            'roles' => $user->getRoleNames(),
            'subscribed' => $user->subscribed('default'),
        ]);
    }
}
