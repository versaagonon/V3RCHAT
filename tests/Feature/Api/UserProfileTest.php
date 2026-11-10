<?php

declare(strict_types=1);

namespace Chatify\Tests\Feature\Api;

use Chatify\Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserProfileTest extends TestCase
{
    public function test_user_can_view_own_profile(): void
    {
        $user = $this->createUser([
            'name' => 'Versaa Sovereign',
            'username' => 'versaa_dev',
            'email' => 'versaa@v3rchat.io',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/chatify/v1/profile');

        $response->assertOk()
            ->assertJsonPath('data.type', 'user')
            ->assertJsonPath('data.attributes.name', 'Versaa Sovereign')
            ->assertJsonPath('data.attributes.username', 'versaa_dev')
            ->assertJsonPath('data.attributes.email', 'versaa@v3rchat.io');
    }

    public function test_user_can_update_name_and_username_without_password(): void
    {
        $user = $this->createUser([
            'name' => 'Initial Name',
            'username' => 'initial_user',
            'email' => 'user@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->actingAs($user, 'sanctum')->patchJson('/api/chatify/v1/profile', [
            'name' => 'Updated Name',
            'username' => 'updated_handle',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.attributes.name', 'Updated Name')
            ->assertJsonPath('data.attributes.username', 'updated_handle');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'username' => 'updated_handle',
            'email' => 'user@example.com',
        ]);
    }

    public function test_changing_email_requires_current_password(): void
    {
        $user = $this->createUser([
            'name' => 'Test User',
            'email' => 'old_email@example.com',
            'password' => Hash::make('correct_password'),
        ]);

        // Attempt without password
        $responseWithoutPassword = $this->actingAs($user, 'sanctum')->patchJson('/api/chatify/v1/profile', [
            'email' => 'new_email@example.com',
        ]);

        $responseWithoutPassword->assertUnprocessable()
            ->assertJsonValidationErrors(['current_password']);

        // Attempt with incorrect password
        $responseWithWrongPassword = $this->actingAs($user, 'sanctum')->patchJson('/api/chatify/v1/profile', [
            'email' => 'new_email@example.com',
            'current_password' => 'wrong_password',
        ]);

        $responseWithWrongPassword->assertUnprocessable()
            ->assertJsonValidationErrors(['current_password']);

        // Attempt with correct password succeeds
        $responseSuccess = $this->actingAs($user, 'sanctum')->patchJson('/api/chatify/v1/profile', [
            'email' => 'new_email@example.com',
            'current_password' => 'correct_password',
        ]);

        $responseSuccess->assertOk()
            ->assertJsonPath('data.attributes.email', 'new_email@example.com');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'new_email@example.com',
        ]);
    }

    public function test_user_can_upload_avatar_via_profile(): void
    {
        Storage::fake('public');
        $user = $this->createUser([
            'name' => 'Avatar Tester',
            'password' => Hash::make('password123'),
        ]);

        $file = UploadedFile::fake()->image('profile_pic.png', 120, 120);

        $response = $this->actingAs($user, 'sanctum')->post('/api/chatify/v1/profile', [
            'name' => 'Avatar Tester Updated',
            'avatar' => $file,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Avatar Tester Updated',
        ]);
    }
}
