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

        $whispers = Whisper::with(['user.profile', 'parent.user.profile'])
            ->withCount(['likedBy', 'retweets'])
            ->whereIn('user_id', $followingIds)
            ->orderByDesc('created_at')
            ->limit($this->limit($request))
            ->get();

        return response()->json([
            'whisper' => $this->withViewerStates($whispers, $user->id),
        ]);
    }

    public function all(Request $request): JsonResponse
    {
        $user = $request->user();

        $whispers = Whisper::with(['user.profile', 'parent.user.profile'])
            ->withCount(['likedBy', 'retweets'])
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
            ->loadCount(['likedBy', 'retweets']);

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
        $whispers = Whisper::with(['user.profile', 'parent.user.profile'])
            ->withCount(['likedBy', 'retweets'])
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

    public function likedByUser(Request $request, string $id): JsonResponse
    {
        $target = User::findOrFail($id);
        $whispers = $target->likedWhispers()
            ->with(['user.profile', 'parent.user.profile'])
            ->withCount(['likedBy', 'retweets'])
            ->orderByDesc('likes.created_at')
            ->limit($this->limit($request))
            ->get();

        return response()->json([
            'whisper' => $this->withViewerStates($whispers, $request->user()->id),
        ]);
    }

    public function retweet(Request $request, string $id): JsonResponse
    {
        $original = Whisper::findOrFail($id);
        $user = $request->user();

        $existing = Whisper::where('user_id', $user->id)
            ->where('whisper_id', $original->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $retweeted = false;
        } else {
            Whisper::create([
                'user_id' => $user->id,
                'content' => '',
                'whisper_id' => $original->id,
            ]);
            $retweeted = true;
        }

        $retweetCount = Whisper::where('whisper_id', $original->id)->count();

        return response()->json([
            'retweeted' => $retweeted,
            'retweet_count' => $retweetCount,
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
        $parentIds = $whispers->pluck('whisper_id')->filter()->values();
        $allIds = $ids->merge($parentIds)->unique();

        // いいね状態
        $likedIds = User::findOrFail($viewerId)
            ->likedWhispers()
            ->whereIn('whispers.id', $allIds)
            ->pluck('whispers.id')
            ->flip();

        // リツイート状態（自分がリツイートしているささやきのID）
        $retweetedIds = Whisper::where('user_id', $viewerId)
            ->whereNotNull('whisper_id')
            ->whereIn('whisper_id', $allIds)
            ->pluck('whisper_id')
            ->flip();

        return $whispers->map(function (Whisper $whisper) use ($likedIds, $retweetedIds): array {
            $data = $whisper->toArray();
            $data['liked_by_me'] = $likedIds->has($whisper->id);
            $data['retweeted_by_me'] = $retweetedIds->has($whisper->id);

            // リツイートの場合、親ささやきにも viewer states を付与
            if (isset($data['parent']) && $whisper->whisper_id) {
                $pid = $whisper->whisper_id;
                $data['parent']['liked_by_me'] = $likedIds->has($pid);
                $data['parent']['retweeted_by_me'] = $retweetedIds->has($pid);
                $data['parent']['liked_by_count'] = $data['parent']['liked_by_count'] ?? 0;
                $data['parent']['retweets_count'] = $data['parent']['retweets_count'] ?? 0;
            }

            return $data;
        });
    }

    private function limit(Request $request): int
    {
        return min(max((int) $request->query('limit', 50), 1), 100);
    }
}
