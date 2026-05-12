<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Whisper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WhisperController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $followingIds = $user->follows()->pluck('users.id');
        $followingIds->push($user->id);

        $whispers = Whisper::with('user')
            ->whereIn('user_id', $followingIds)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($whispers);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'text' => 'required|string|max:255',
        ]);

        Whisper::create([
            'user_id' => Auth::id(),
            'content' => $validated['text'],
        ]);

        return response()->json([
            'message' => 'Whisper created.',
        ], 201);
    }

    public function show(string $id)
    {
        $user = User::findOrFail($id);

        $whispers = Whisper::where('user_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'user' => $user,
            'whispers' => $whispers,
        ]);
    }

    public function update(Request $request, string $id)
    {
        //
    }

    public function destroy(string $id)
    {
        $whisper = Whisper::findOrFail($id);

        if (Auth::id() !== $whisper->user_id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $whisper->delete();

        return response()->json(['message' => 'Whisper deleted.']);
    }
}
