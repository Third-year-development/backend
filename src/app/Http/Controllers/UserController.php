<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error.',
                'errors' => $validator->errors(),
            ], 422);
        }

        [$user, $token] = DB::transaction(function () use ($request): array {

            $user = User::create([
                'name' => $request->string('name')->toString(),
                'email' => $request->string('email')->toString(),
                'password' => Hash::make(
                    $request->string('password')->toString()
                ),
            ]);

            UserProfile::create([
                'user_id' => $user->id,
                'profile' => null,
                'icon_file_name' => null,
            ]);

            $token = $user->createToken('mobile')->plainTextToken;

            return [$user->load('profile'), $token];
        });

        return response()->json([
            'token' => $token,
            'user' => $user,
            'userprofile' => $user,
        ], 201);
    }

    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'userprofile' => $request->user()
                ->load('profile')
                ->loadCount(['follows', 'followers']),
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $loginUser = $request->user();

        if ((string) $loginUser->id !== $id) {
            return response()->json([
                'message' => 'You can only update your own user profile.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'profile' => 'nullable|string|max:255',
            'iconfile' => 'nullable|file|image|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error.',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::transaction(function () use ($request, $loginUser): void {

            $loginUser->update([
                'name' => $request->string('name')->toString(),
            ]);

            $profile = $loginUser->profile
                ?? new UserProfile(['user_id' => $loginUser->id]);

            $profile->user_id = $loginUser->id;
            $profile->profile = $request->input('profile');

            if ($request->hasFile('iconfile')) {

                if ($profile->icon_file_name) {
                    Storage::disk('public')
                        ->delete($profile->icon_file_name);
                }

                $profile->icon_file_name = $request
                    ->file('iconfile')
                    ->store('icons', 'public');
            }

            $profile->save();
        });

        $user = $loginUser->fresh()
            ->load('profile')
            ->loadCount(['follows', 'followers']);

        return response()->json([
            'user' => $user,
            'userprofile' => $user,
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $loginUser = $request->user();

        if ((string) $loginUser->id !== $id) {
            return response()->json([
                'message' => 'You can only delete your own user profile.',
            ], 403);
        }

        DB::transaction(function () use ($loginUser): void {

            $iconPath = $loginUser->profile?->icon_file_name;

            if ($iconPath) {
                Storage::disk('public')->delete($iconPath);
            }

            $loginUser->currentAccessToken()?->delete();

            $loginUser->delete();
        });

        return response()->json([
            'message' => 'User deleted.',
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $keyword = $request->query('keyword');

        $users = User::where('name', 'LIKE', "%{$keyword}%")
            ->select('id', 'name')
            ->limit(20)
            ->get();

        return response()->json([
            'users' => $users,
        ]);
    }

    public function followers(string $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $followers = $user->followers()
            ->select('users.id', 'users.name')
            ->get();

        return response()->json([
            'followers' => $followers,
        ]);
    }
}
