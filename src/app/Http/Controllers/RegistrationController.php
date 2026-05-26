<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Whisper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RegistrationController extends Controller
{
    public function followRegister(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'follow_user_id' => 'required|integer|exists:users,id',
            'following' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $followUserId = (int) $request->input('follow_user_id');

        if ($user->id === $followUserId) {
            return response()->json([
                'message' => 'You cannot follow yourself.',
            ], 422);
        }

        $target = User::findOrFail($followUserId);
        $isFollowing = $user->isFollowing($target->id);
        $desiredState = $request->has('following')
            ? $request->boolean('following')
            : !$isFollowing;

        if ($desiredState) {
            $user->follows()->syncWithoutDetaching([$target->id]);
            $following = true;
            $message = $isFollowing ? 'Already following.' : 'Follow added.';
        } else {
            $user->follows()->detach($target->id);
            $following = false;
            $message = $isFollowing ? 'Follow removed.' : 'Already not following.';
        }

        return response()->json([
            'message' => $message,
            'following' => $following,
        ]);
    }

    public function likeRegister(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'whisper_id' => 'required|integer|exists:whispers,id',
            'liked' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $whisper = Whisper::findOrFail((int) $request->input('whisper_id'));
        $liked = $user->likedWhispers()
            ->where('whispers.id', $whisper->id)
            ->exists();
        $wasLiked = $liked;
        $desiredState = $request->has('liked')
            ? $request->boolean('liked')
            : !$liked;

        if ($desiredState) {
            $user->likedWhispers()->syncWithoutDetaching([$whisper->id]);
            $liked = true;
            $message = $wasLiked ? 'Already liked.' : 'Like added.';
        } else {
            $user->likedWhispers()->detach($whisper->id);
            $liked = false;
            $message = $wasLiked ? 'Like removed.' : 'Already not liked.';
        }

        $likesCount = $whisper->likedBy()->count();

        return response()->json([
            'message' => $message,
            'liked' => $liked,
            'liked_by_count' => $likesCount,
            'likes_count' => $likesCount,
        ]);
    }
}
