<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\StoreBranchRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StoreBranchRequestTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_requires_super_admin_role_for_authorization()
    {
        // Create super-admin role
        Role::create(['name' => 'super-admin', 'guard_name' => 'web']);
        
        // Regular user without super-admin role
        $regularUser = User::factory()->create();
        $this->actingAs($regularUser);
        
        $request = new StoreBranchRequest();
        $this->assertFalse($request->authorize());
        
        // Super-admin user
        $adminUser = User::factory()->create();
        $adminUser->assignRole('super-admin');
        $this->actingAs($adminUser);
        
        $request = new StoreBranchRequest();
        $this->assertTrue($request->authorize());
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $request = new StoreBranchRequest();
        
        $validator = Validator::make([], $request->rules());
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('code', $validator->errors()->toArray());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    /** @test */
    public function it_validates_code_format()
    {
        $request = new StoreBranchRequest();
        
        // Code too long
        $validator = Validator::make([
            'code' => 'VERYLONGCODE123',
            'name' => 'فرع تجريبي',
        ], $request->rules());
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('code', $validator->errors()->toArray());
    }

    /** @test */
    public function it_validates_code_uniqueness()
    {
        // Create existing branch
        \App\Models\Branch::factory()->create(['code' => 'TST']);
        
        $request = new StoreBranchRequest();
        
        $validator = Validator::make([
            'code' => 'TST',
            'name' => 'فرع آخر',
        ], $request->rules());
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('code', $validator->errors()->toArray());
    }

    /** @test */
    public function it_validates_name_uniqueness()
    {
        // Create existing branch
        \App\Models\Branch::factory()->create(['name' => 'فرع موجود']);
        
        $request = new StoreBranchRequest();
        
        $validator = Validator::make([
            'code' => 'NEW',
            'name' => 'فرع موجود',
        ], $request->rules());
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    /** @test */
    public function it_accepts_valid_data()
    {
        $request = new StoreBranchRequest();
        
        $validator = Validator::make([
            'code' => 'TST',
            'name' => 'فرع تجريبي',
            'location' => 'القاهرة',
            'phone' => '01012345678',
            'is_active' => true,
        ], $request->rules());
        
        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function it_has_arabic_error_messages()
    {
        $request = new StoreBranchRequest();
        $messages = $request->messages();
        
        $this->assertArrayHasKey('code.required', $messages);
        $this->assertArrayHasKey('code.unique', $messages);
        $this->assertArrayHasKey('name.required', $messages);
        $this->assertArrayHasKey('name.unique', $messages);
        
        // Check messages are in Arabic
        $this->assertStringContainsString('كود', $messages['code.required']);
        $this->assertStringContainsString('اسم', $messages['name.required']);
    }
}
