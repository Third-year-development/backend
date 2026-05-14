<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
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
            ->assertJsonPath('userprofile.name', 'Updated User')
            ->assertJsonPath('userprofile.profile.profile', 'after');
    }
}
