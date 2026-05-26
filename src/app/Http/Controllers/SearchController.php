<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Whisper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class SearchController extends Controller
{
    public function usernameSearch(Request $request, string $keyword): JsonResponse
    {
        if (mb_strlen($keyword) < 2) {
            return response()->json([
                'message' => 'Keyword must be at least 2 characters.',
            ], 422);
        }

        $users = User::with('profile')
            ->withCount(['follows', 'followers'])
            ->where('name', 'like', '%'.$keyword.'%')
            ->orderBy('name')
            ->limit($this->limit($request))
            ->get();

        return response()->json([
            'user_line' => $users,
        ]);
    }

    public function whisperSearch(Request $request, string $keyword): JsonResponse
    {
        if (mb_strlen($keyword) < 2) {
            return response()->json([
                'message' => 'Keyword must be at least 2 characters.',
            ], 422);
        }

        $whispers = Whisper::with(['user.profile'])
            ->withCount('likedBy')
            ->where('content', 'like', '%'.$keyword.'%')
            ->orderByDesc('created_at')
            ->limit($this->limit($request))
            ->get();

        return response()->json([
            'whisper' => $this->withViewerStates($whispers, $request->user()->id),
        ]);
    }

    private function withViewerStates(Collection $whispers, int $viewerId): Collection
    {
        $likedIds = User::findOrFail($viewerId)
            ->likedWhispers()
            ->whereIn('whispers.id', $whispers->pluck('id'))
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
