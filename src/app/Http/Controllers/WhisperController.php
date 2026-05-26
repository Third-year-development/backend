<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Whisper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

class WhisperController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $followingIds = $user->follows()->pluck('users.id')->push($user->id)->unique()->values();

        $whispers = Whisper::with(['user.profile'])
            ->withCount('likedBy')
            ->whereIn('user_id', $followingIds)
            ->orderByDesc('created_at')
            ->limit($this->limit($request))
            ->get();

        return response()->json([
            'whisper' => $this->withViewerStates($whispers, $user->id),
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
        ])->load(['user.profile'])
            ->loadCount('likedBy');

        return response()->json([
            'message' => 'Whisper created.',
            'whisper' => $this->withViewerState($whisper, $request->user()->id),
        ], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $user = User::with('profile')
            ->withCount(['follows', 'followers'])
            ->findOrFail($id);
        $whispers = Whisper::with(['user.profile'])
            ->withCount('likedBy')
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit($this->limit($request))
            ->get();

        return response()->json([
            'user_line' => $user,
            'userprofile' => $user,
            'whisper' => $this->withViewerStates($whispers, $request->user()->id),
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

    private function withViewerState(Whisper $whisper, int $viewerId): array
    {
        return $this->withViewerStates(collect([$whisper]), $viewerId)->first();
    }

    private function withViewerStates(Collection $whispers, int $viewerId): Collection
    {
        $ids = $whispers->pluck('id');
        $likedIds = User::findOrFail($viewerId)
            ->likedWhispers()
            ->whereIn('whispers.id', $ids)
            ->pluck('whispers.id')
            ->flip();

        return $whispers->map(function (Whisper $whisper) use ($likedIds): array {
            $data = $whisper->toArray();
            $data['liked_by_me'] = $likedIds->has($whisper->id);

            return $data;
        });
    }

    private function limit(Request $request): int
    {
        return min(max((int) $request->query('limit', 50), 1), 100);
    }
}
