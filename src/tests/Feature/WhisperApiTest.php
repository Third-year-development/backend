<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Whisper;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WhisperApiTest extends TestCase
{
    use DatabaseTransactions;

    public function test_store_creates_whisper_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        $user->profile()->create([
            'profile' => null,
            'icon_file_name' => null,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/whispers', [
            'text' => 'first whisper',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('whisper.content', 'first whisper')
            ->assertJsonPath('whisper.user_id', $user->id);

        $this->assertDatabaseHas('whispers', [
            'user_id' => $user->id,
            'content' => 'first whisper',
        ]);
    }

    public function test_index_returns_followed_users_and_own_whispers(): void
    {
        $user = User::factory()->create();
        $followedUser = User::factory()->create();
        $otherUser = User::factory()->create();

        $user->profile()->create(['profile' => null, 'icon_file_name' => null]);
        $followedUser->profile()->create(['profile' => null, 'icon_file_name' => null]);
        $otherUser->profile()->create(['profile' => null, 'icon_file_name' => null]);

        $user->follows()->attach($followedUser->id);

        Whisper::create([
            'user_id' => $user->id,
            'content' => 'own whisper',
            'whisper_id' => null,
        ]);
        Whisper::create([
            'user_id' => $followedUser->id,
            'content' => 'followed whisper',
            'whisper_id' => null,
        ]);
        Whisper::create([
            'user_id' => $otherUser->id,
            'content' => 'hidden whisper',
            'whisper_id' => null,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/whispers');

        $response->assertOk();
        $response->assertJsonFragment(['content' => 'own whisper']);
        $response->assertJsonFragment(['content' => 'followed whisper']);
        $response->assertJsonMissing(['content' => 'hidden whisper']);
    }

    public function test_destroy_uses_post_route(): void
    {
        $user = User::factory()->create();
        $user->profile()->create([
            'profile' => null,
            'icon_file_name' => null,
        ]);

        $whisper = Whisper::create([
            'user_id' => $user->id,
            'content' => 'delete me',
            'whisper_id' => null,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/whispers/'.$whisper->id);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Whisper deleted.');

        $this->assertDatabaseMissing('whispers', [
            'id' => $whisper->id,
        ]);
    }
}
