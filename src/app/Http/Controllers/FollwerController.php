<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FollwerController extends Controller
{
    public function following(Request $request): JsonResponse
    {
        $users = $request->user()
            ->follows()
            ->with('profile')
            ->withCount(['follows', 'followers'])
            ->orderBy('name')
            ->limit($this->limit($request))
            ->get();

        return response()->json([
            'user_line' => $users,
        ]);
    }

    public function followers(Request $request): JsonResponse
    {
        $users = $request->user()
            ->followers()
            ->with('profile')
            ->withCount(['follows', 'followers'])
            ->orderBy('name')
            ->limit($this->limit($request))
            ->get();

        return response()->json([
            'user_line' => $users,
        ]);
    }

    private function limit(Request $request): int
    {
        return min(max((int) $request->query('limit', 50), 1), 100);
    }
}
