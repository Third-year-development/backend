<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserApiTest extends TestCase
{
    use DatabaseTransactions;

    public function test_user_register_creates_profile_and_returns_token(): void
    {
        $response = $this->postJson('/api/v1/users/register', [
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'password',
        ]);

        $response
            ->assertCreated()
            ->assertJsonStructure([
                'token',
                'user' => ['id', 'name', 'email', 'profile'],
                'userprofile' => ['id', 'name', 'email', 'profile'],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'alice@example.com',
        ]);

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => User::where('email', 'alice@example.com')->value('id'),
        ]);
    }

    public function test_user_endpoint_returns_authenticated_user_with_profile(): void
    {
        $user = User::factory()->create();
        $user->profile()->create([
            'profile' => 'hello',
            'icon_file_name' => null,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/user');

        $response
            ->assertOk()
            ->assertJsonPath('userprofile.id', $user->id)
            ->assertJsonPath('userprofile.profile.profile', 'hello');
    }

    public function test_user_update_returns_userprofile_key(): void
    {
        $user = User::factory()->create();
        $user->profile()->create([
            'profile' => 'before',
            'icon_file_name' => null,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/users/profile/'.$user->id, [
            'name' => 'Updated User',
            'profile' => 'after',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('user.name', 'Updated User')
            ->assertJsonPath('userprofile.name', 'Updated User')
            ->assertJsonPath('userprofile.profile.profile', 'after');
    }

    public function test_user_update_can_store_profile_icon(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $user->profile()->create([
            'profile' => null,
            'icon_file_name' => null,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/users/profile/'.$user->id, [
            'name' => 'Icon User',
            'profile' => 'with icon',
            'iconfile' => UploadedFile::fake()->image('avatar.jpg'),
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('userprofile.name', 'Icon User');

        $iconPath = $user->fresh()->profile->icon_file_name;
        $this->assertNotNull($iconPath);
        Storage::disk('public')->assertExists($iconPath);
    }

    public function test_user_update_and_delete_reject_other_users(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/users/profile/'.$other->id, [
            'name' => 'Blocked',
        ])->assertForbidden();

        $this->postJson('/api/v1/users/delete/'.$other->id)
            ->assertForbidden();
    }

    public function test_user_destroy_deletes_own_user(): void
    {
        $user = User::factory()->create();
        $user->profile()->create([
            'profile' => 'delete',
            'icon_file_name' => null,
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/users/delete/'.$user->id)
            ->assertOk()
            ->assertJsonPath('message', 'User deleted.');

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('user_profiles', ['user_id' => $user->id]);
    }
}
