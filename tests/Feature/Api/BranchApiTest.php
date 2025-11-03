<?php

namespace Tests\Feature\Api;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BranchApiTest extends TestCase
{
    use RefreshDatabase;

    protected function authenticatedUser($isSuperAdmin = false)
    {
        $user = User::factory()->create();
        
        if ($isSuperAdmin) {
            // Create super-admin role if not exists
            if (!Role::where('name', 'super-admin')->exists()) {
                Role::create(['name' => 'super-admin', 'guard_name' => 'web']);
            }
            $user->assignRole('super-admin');
        }
        
        $token = $user->createToken('test-token')->plainTextToken;
        return ['user' => $user, 'token' => $token];
    }

    /** @test */
    public function it_can_list_all_branches()
    {
        // Arrange
        Branch::factory()->count(3)->create();
        $auth = $this->authenticatedUser();

        // Act
        $response = $this->withHeader('Authorization', "Bearer {$auth['token']}")
            ->getJson('/api/v1/branches');

        // Assert
        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function it_can_create_a_branch()
    {
        // Arrange
        $auth = $this->authenticatedUser(true); // super-admin required
        $branchData = [
            'code' => 'TST',
            'name' => 'فرع تجريبي',
            'location' => 'القاهرة',
            'is_active' => true,
        ];

        // Act
        $response = $this->withHeader('Authorization', "Bearer {$auth['token']}")
            ->postJson('/api/v1/branches', $branchData);

        // Assert
        $response->assertStatus(201)
            ->assertJsonPath('data.code', 'TST')
            ->assertJsonPath('data.name', 'فرع تجريبي');

        $this->assertDatabaseHas('branches', [
            'code' => 'TST',
            'name' => 'فرع تجريبي',
        ]);
    }

    /** @test */
    public function it_prevents_duplicate_branch_codes()
    {
        // Arrange
        Branch::factory()->create(['code' => 'DUP']);
        $auth = $this->authenticatedUser(true); // super-admin required

        // Act
        $response = $this->withHeader('Authorization', "Bearer {$auth['token']}")
            ->postJson('/api/v1/branches', [
                'code' => 'DUP',
                'name' => 'فرع آخر',
            ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    /** @test */
    public function it_can_show_a_single_branch()
    {
        // Arrange
        $branch = Branch::factory()->create(['name' => 'فرع اختبار']);
        $auth = $this->authenticatedUser();

        // Act
        $response = $this->withHeader('Authorization', "Bearer {$auth['token']}")
            ->getJson("/api/v1/branches/{$branch->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'فرع اختبار');
    }

    /** @test */
    public function it_can_update_a_branch()
    {
        // Arrange
        $branch = Branch::factory()->create(['name' => 'اسم قديم']);
        $auth = $this->authenticatedUser(true); // super-admin required

        // Act
        $response = $this->withHeader('Authorization', "Bearer {$auth['token']}")
            ->putJson("/api/v1/branches/{$branch->id}", [
                'name' => 'اسم جديد',
            ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'اسم جديد');

        $this->assertDatabaseHas('branches', [
            'id' => $branch->id,
            'name' => 'اسم جديد',
        ]);
    }

    /** @test */
    public function it_prevents_deleting_core_branches()
    {
        // Arrange
        $branch = Branch::factory()->create(['code' => 'FAC']);
        $auth = $this->authenticatedUser();

        // Act
        $response = $this->withHeader('Authorization', "Bearer {$auth['token']}")
            ->deleteJson("/api/v1/branches/{$branch->id}");

        // Assert
        $response->assertStatus(422)
            ->assertJsonPath('message', 'لا يمكن حذف الفروع الأساسية (المصنع، العتبة، إمبابة)');

        $this->assertDatabaseHas('branches', ['id' => $branch->id]);
    }

    /** @test */
    public function it_can_delete_a_non_core_branch()
    {
        // Arrange
        $branch = Branch::factory()->create(['code' => 'TEST']);
        $auth = $this->authenticatedUser();

        // Act
        $response = $this->withHeader('Authorization', "Bearer {$auth['token']}")
            ->deleteJson("/api/v1/branches/{$branch->id}");

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseMissing('branches', ['id' => $branch->id]);
    }
}
