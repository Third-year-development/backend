<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Whisper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WhisperController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $followingIds = $user->follows()->pluck('users.id')->push($user->id)->unique()->values();

        $whispers = Whisper::with(['user.profile'])
            ->whereIn('user_id', $followingIds)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'whisper' => $whispers,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'text' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $whisper = Whisper::create([
            'user_id' => $request->user()->id,
            'content' => $request->string('text')->toString(),
            'whisper_id' => null,
        ])->load(['user.profile']);

        return response()->json([
            'message' => 'Whisper created.',
            'whisper' => $whisper,
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $user = User::with('profile')->findOrFail($id);
        $whispers = Whisper::with(['user.profile'])
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'userprofile' => $user,
            'whisper' => $whispers,
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $whisper = Whisper::findOrFail($id);

        if ($whisper->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'You can only delete your own whisper.',
            ], 403);
        }

        $whisper->delete();

        return response()->json([
            'message' => 'Whisper deleted.',
        ]);
    }
}
