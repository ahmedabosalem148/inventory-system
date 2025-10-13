<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_login_with_valid_credentials()
    {
        // Arrange
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        // Act
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'user' => ['id', 'name', 'email'],
                'token',
                'token_type',
            ]);
        
        $this->assertNotEmpty($response->json('token'));
        $this->assertEquals('Bearer', $response->json('token_type'));
    }

    /** @test */
    public function it_cannot_login_with_invalid_credentials()
    {
        // Arrange
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        // Act
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function it_requires_email_and_password_to_login()
    {
        // Act
        $response = $this->postJson('/api/v1/auth/login', []);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    /** @test */
    public function it_can_get_authenticated_user_profile()
    {
        // Arrange
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        // Act
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/auth/me');

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ]);
    }

    /** @test */
    public function it_cannot_access_protected_routes_without_token()
    {
        // Act
        $response = $this->getJson('/api/v1/auth/me');

        // Assert
        $response->assertStatus(401);
    }

    /** @test */
    public function it_can_logout_and_revoke_token()
    {
        // Arrange
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        // Act
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/logout');

        // Assert
        $response->assertStatus(200);
        
        // Verify token is revoked
        $meResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/auth/me');
        
        $meResponse->assertStatus(401);
    }

    /** @test */
    public function it_can_update_user_profile()
    {
        // Arrange
        $user = User::factory()->create(['name' => 'Old Name']);
        $token = $user->createToken('test-token')->plainTextToken;

        // Act
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson('/api/v1/auth/profile', [
                'name' => 'New Name',
            ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'user' => [
                    'name' => 'New Name',
                ],
            ]);
        
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name',
        ]);
    }

    /** @test */
    public function it_can_change_password()
    {
        // Arrange
        $user = User::factory()->create([
            'password' => bcrypt('old-password'),
        ]);
        $token = $user->createToken('test-token')->plainTextToken;

        // Act
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/change-password', [
                'current_password' => 'old-password',
                'new_password' => 'new-password',
                'new_password_confirmation' => 'new-password',
            ]);

        // Assert
        $response->assertStatus(200);
        
        // Verify can login with new password
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'new-password',
        ]);
        
        $loginResponse->assertStatus(200);
    }

    /** @test */
    public function it_revokes_all_tokens_after_password_change()
    {
        // Arrange
        $user = User::factory()->create([
            'password' => bcrypt('old-password'),
        ]);
        $oldToken = $user->createToken('old-token')->plainTextToken;

        // Act
        $response = $this->withHeader('Authorization', "Bearer {$oldToken}")
            ->postJson('/api/v1/auth/change-password', [
                'current_password' => 'old-password',
                'new_password' => 'new-password',
                'new_password_confirmation' => 'new-password',
            ]);

        // Assert
        $response->assertStatus(200);
        
        // Verify old token no longer works
        $meResponse = $this->withHeader('Authorization', "Bearer {$oldToken}")
            ->getJson('/api/v1/auth/me');
        
        $meResponse->assertStatus(401);
    }
}
