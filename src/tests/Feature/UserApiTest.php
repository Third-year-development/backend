<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_creates_user_profile(): void
    {
        $response = $this->postJson('/api/v1/users/register', [
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'secret12',
        ]);

        $response->assertCreated();

        $user = User::where('email', 'alice@example.com')->firstOrFail();

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'profile' => '',
        ]);

        $response->assertJsonPath('user.profile.user_id', $user->id);
    }

    public function test_authenticated_user_endpoint_returns_profile(): void
    {
        $user = User::factory()->create();
        $user->profile()->create([
            'profile' => 'hello profile',
            'icon_file_name' => 'icon.png',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/user');

        $response
            ->assertOk()
            ->assertJsonPath('id', $user->id)
            ->assertJsonPath('profile.profile', 'hello profile')
            ->assertJsonPath('profile.icon_file_name', 'icon.png');
    }
}
