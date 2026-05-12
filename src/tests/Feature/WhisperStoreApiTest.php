<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WhisperStoreApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_store_whisper(): void
    {
        $response = $this->postJson('/api/v1/whispers', [
            'text' => 'hello',
        ]);

        $response->assertUnauthorized();
    }

    public function test_store_whisper_requires_text(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson('/api/v1/whispers', [
            'text' => '',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['text']);
    }

    public function test_authenticated_user_can_store_whisper(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/whispers', [
            'text' => 'first whisper',
        ]);

        $response
            ->assertCreated()
            ->assertJson([
                'message' => 'Whisper created.',
            ]);

        $this->assertDatabaseHas('whispers', [
            'user_id' => $user->id,
            'content' => 'first whisper',
        ]);
    }
}
