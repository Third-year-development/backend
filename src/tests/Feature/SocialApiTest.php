<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Whisper;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SocialApiTest extends TestCase
{
    use DatabaseTransactions;

    public function test_user_search_returns_matching_users(): void
    {
        $user = User::factory()->create(['name' => 'Viewer']);
        $match = User::factory()->create(['name' => 'Alice Whisper']);
        User::factory()->create(['name' => 'Bob']);
        $match->profile()->create(['profile' => 'hello', 'icon_file_name' => null]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/search/users/Alice');

        $response
            ->assertOk()
            ->assertJsonFragment(['name' => 'Alice Whisper'])
            ->assertJsonMissing(['name' => 'Bob']);
    }

    public function test_user_search_rejects_short_keyword(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/v1/search/users/A')
            ->assertStatus(422)
            ->assertJsonPath('message', 'Keyword must be at least 2 characters.');
    }

    public function test_whisper_search_returns_matching_whispers(): void
    {
        $user = User::factory()->create();
        $author = User::factory()->create();
        $author->profile()->create(['profile' => null, 'icon_file_name' => null]);

        Whisper::create(['user_id' => $author->id, 'content' => 'search target', 'whisper_id' => null]);
        Whisper::create(['user_id' => $author->id, 'content' => 'hidden text', 'whisper_id' => null]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/search/whispers/target');

        $response
            ->assertOk()
            ->assertJsonFragment(['content' => 'search target'])
            ->assertJsonMissing(['content' => 'hidden text']);
    }

    public function test_whisper_search_rejects_short_keyword(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/v1/search/whispers/a')
            ->assertStatus(422)
            ->assertJsonPath('message', 'Keyword must be at least 2 characters.');
    }

    public function test_follow_register_toggles_following_state(): void
    {
        $user = User::factory()->create();
        $target = User::factory()->create();

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/followcheck', [
            'follow_user_id' => $target->id,
        ])
            ->assertOk()
            ->assertJsonPath('following', true);

        $this->assertTrue($user->fresh()->isFollowing($target->id));

        $this->postJson('/api/v1/followcheck', [
            'follow_user_id' => $target->id,
        ])
            ->assertOk()
            ->assertJsonPath('following', false);

        $this->assertFalse($user->fresh()->isFollowing($target->id));
    }

    public function test_follow_register_accepts_idempotent_desired_state(): void
    {
        $user = User::factory()->create();
        $target = User::factory()->create();

        Sanctum::actingAs($user);

        $payload = [
            'follow_user_id' => $target->id,
            'following' => true,
        ];

        $this->postJson('/api/v1/followcheck', $payload)
            ->assertOk()
            ->assertJsonPath('following', true);

        $this->postJson('/api/v1/followcheck', $payload)
            ->assertOk()
            ->assertJsonPath('following', true);

        $this->assertEquals(1, $user->fresh()->follows()->where('users.id', $target->id)->count());

        $payload['following'] = false;

        $this->postJson('/api/v1/followcheck', $payload)
            ->assertOk()
            ->assertJsonPath('following', false);

        $this->postJson('/api/v1/followcheck', $payload)
            ->assertOk()
            ->assertJsonPath('following', false);

        $this->assertFalse($user->fresh()->isFollowing($target->id));
    }

    public function test_follow_register_rejects_self_and_missing_user(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/followcheck', [
            'follow_user_id' => $user->id,
            'following' => true,
        ])->assertStatus(422);

        $this->postJson('/api/v1/followcheck', [
            'follow_user_id' => 999999,
            'following' => true,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['follow_user_id']);
    }

    public function test_like_register_toggles_like_state(): void
    {
        $user = User::factory()->create();
        $author = User::factory()->create();
        $whisper = Whisper::create([
            'user_id' => $author->id,
            'content' => 'like me',
            'whisper_id' => null,
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/likecheck', [
            'whisper_id' => $whisper->id,
        ])
            ->assertOk()
            ->assertJsonPath('liked', true)
            ->assertJsonPath('likes_count', 1);

        $this->postJson('/api/v1/likecheck', [
            'whisper_id' => $whisper->id,
        ])
            ->assertOk()
            ->assertJsonPath('liked', false)
            ->assertJsonPath('likes_count', 0);
    }

    public function test_like_register_accepts_idempotent_desired_state(): void
    {
        $user = User::factory()->create();
        $author = User::factory()->create();
        $whisper = Whisper::create([
            'user_id' => $author->id,
            'content' => 'like me once',
            'whisper_id' => null,
        ]);

        Sanctum::actingAs($user);

        $payload = [
            'whisper_id' => $whisper->id,
            'liked' => true,
        ];

        $this->postJson('/api/v1/likecheck', $payload)
            ->assertOk()
            ->assertJsonPath('liked', true)
            ->assertJsonPath('liked_by_count', 1)
            ->assertJsonPath('likes_count', 1);

        $this->postJson('/api/v1/likecheck', $payload)
            ->assertOk()
            ->assertJsonPath('liked', true)
            ->assertJsonPath('liked_by_count', 1)
            ->assertJsonPath('likes_count', 1);

        $payload['liked'] = false;

        $this->postJson('/api/v1/likecheck', $payload)
            ->assertOk()
            ->assertJsonPath('liked', false)
            ->assertJsonPath('liked_by_count', 0)
            ->assertJsonPath('likes_count', 0);

        $this->postJson('/api/v1/likecheck', $payload)
            ->assertOk()
            ->assertJsonPath('liked', false)
            ->assertJsonPath('liked_by_count', 0)
            ->assertJsonPath('likes_count', 0);
    }

    public function test_like_register_rejects_missing_whisper(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/likecheck', [
            'whisper_id' => 999999,
            'liked' => true,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['whisper_id']);
    }

    public function test_social_routes_require_authentication(): void
    {
        $this->getJson('/api/v1/search/users/Alice')->assertUnauthorized();
        $this->getJson('/api/v1/search/whispers/target')->assertUnauthorized();
        $this->getJson('/api/v1/following')->assertUnauthorized();
        $this->getJson('/api/v1/followers')->assertUnauthorized();
        $this->postJson('/api/v1/followcheck', ['follow_user_id' => 1])->assertUnauthorized();
        $this->postJson('/api/v1/likecheck', ['whisper_id' => 1])->assertUnauthorized();
    }

    public function test_following_list_respects_limit(): void
    {
        $user = User::factory()->create();
        $targets = User::factory()->count(3)->create();
        $user->follows()->attach($targets->pluck('id'));

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/following?limit=2')
            ->assertOk()
            ->assertJsonCount(2, 'user_line');
    }

    public function test_search_results_respect_limit(): void
    {
        $user = User::factory()->create();
        User::factory()->count(3)->create(['name' => 'Limit Target']);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/search/users/Limit?limit=2')
            ->assertOk()
            ->assertJsonCount(2, 'user_line');
    }
}
