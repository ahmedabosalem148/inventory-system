<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Customer;
use App\Models\IssueVoucher;
use App\Models\Product;
use App\Models\User;
use App\Models\UserBranchPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BranchPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $viewOnlyUser;
    protected User $fullAccessUser;
    protected User $noAccessUser;
    protected Branch $branch1;
    protected Branch $branch2;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        // Create super-admin role if not exists
        if (!\Spatie\Permission\Models\Role::where('name', 'super-admin')->exists()) {
            \Spatie\Permission\Models\Role::create(['name' => 'super-admin', 'guard_name' => 'web']);
        }

        // Create branches
        $this->branch1 = Branch::factory()->create(['name' => 'Branch 1']);
        $this->branch2 = Branch::factory()->create(['name' => 'Branch 2']);

        // Create admin user with super-admin role
        $this->adminUser = User::factory()->create(['email' => 'admin@test.com']);
        $this->adminUser->assignRole('super-admin');

        // Create user with view_only permission on branch1
        $this->viewOnlyUser = User::factory()->create([
            'email' => 'viewonly@test.com',
            'assigned_branch_id' => $this->branch1->id,
            'current_branch_id' => $this->branch1->id,
        ]);
        UserBranchPermission::create([
            'user_id' => $this->viewOnlyUser->id,
            'branch_id' => $this->branch1->id,
            'permission_level' => UserBranchPermission::PERMISSION_VIEW_ONLY,
        ]);

        // Create user with full_access permission on branch1
        $this->fullAccessUser = User::factory()->create([
            'email' => 'fullaccess@test.com',
            'assigned_branch_id' => $this->branch1->id,
            'current_branch_id' => $this->branch1->id,
        ]);
        UserBranchPermission::create([
            'user_id' => $this->fullAccessUser->id,
            'branch_id' => $this->branch1->id,
            'permission_level' => UserBranchPermission::PERMISSION_FULL_ACCESS,
        ]);

        // Create user with no branch assignment
        $this->noAccessUser = User::factory()->create(['email' => 'noaccess@test.com']);

        // Create a test product (without factory)
        $category = Category::create(['name' => 'Test Category', 'code' => 'TEST']);
        $this->product = Product::create([
            'category_id' => $category->id,
            'name' => 'Test Product',
            'code' => 'PROD001',
            'unit' => 'piece',
            'purchase_price' => 100,
            'sale_price' => 150,
        ]);
    }

    // ========================================================================
    // User Model - Branch Methods Tests
    // ========================================================================

    public function test_admin_has_role_super_admin(): void
    {
        $this->assertTrue($this->adminUser->hasRole('super-admin'));
    }

    public function test_user_can_access_branch_with_permission(): void
    {
        $this->assertTrue($this->viewOnlyUser->canAccessBranch($this->branch1->id));
        $this->assertFalse($this->viewOnlyUser->canAccessBranch($this->branch2->id));
    }

    public function test_user_has_full_access_to_branch(): void
    {
        $this->assertTrue($this->fullAccessUser->hasFullAccessToBranch($this->branch1->id));
        $this->assertFalse($this->viewOnlyUser->hasFullAccessToBranch($this->branch1->id));
    }

    public function test_user_get_active_branch(): void
    {
        $activeBranch = $this->fullAccessUser->getActiveBranch();
        $this->assertNotNull($activeBranch);
        $this->assertEquals($this->branch1->id, $activeBranch->id);
    }

    public function test_user_can_switch_branch(): void
    {
        // Add permission for branch2
        UserBranchPermission::create([
            'user_id' => $this->fullAccessUser->id,
            'branch_id' => $this->branch2->id,
            'permission_level' => UserBranchPermission::PERMISSION_FULL_ACCESS,
        ]);

        $result = $this->fullAccessUser->switchBranch($this->branch2->id);
        $this->assertTrue($result);
        $this->assertEquals($this->branch2->id, $this->fullAccessUser->fresh()->current_branch_id);
    }

    public function test_user_cannot_switch_to_unauthorized_branch(): void
    {
        $result = $this->fullAccessUser->switchBranch($this->branch2->id);
        $this->assertFalse($result);
    }

    // ========================================================================
    // UserBranchController Tests
    // ========================================================================

    public function test_user_can_list_authorized_branches(): void
    {
        Sanctum::actingAs($this->fullAccessUser);

        $response = $this->getJson('/api/v1/user/branches');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $this->branch1->id)
            ->assertJsonPath('data.0.permission_level', 'full_access');
    }

    public function test_user_can_get_current_branch(): void
    {
        Sanctum::actingAs($this->fullAccessUser);

        $response = $this->getJson('/api/v1/user/current-branch');

        $response->assertOk()
            ->assertJsonPath('data.branch.id', $this->branch1->id)
            ->assertJsonPath('data.permission_level', 'full_access');
    }

    public function test_user_can_switch_branch_via_api(): void
    {
        // Add permission for branch2
        UserBranchPermission::create([
            'user_id' => $this->fullAccessUser->id,
            'branch_id' => $this->branch2->id,
            'permission_level' => UserBranchPermission::PERMISSION_VIEW_ONLY,
        ]);

        Sanctum::actingAs($this->fullAccessUser);

        $response = $this->postJson('/api/v1/user/switch-branch', [
            'branch_id' => $this->branch2->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.current_branch_id', $this->branch2->id);
    }

    // ========================================================================
    // ProductController - Branch Permission Tests
    // ========================================================================

    public function test_admin_can_view_all_products(): void
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson('/api/v1/products');

        $response->assertOk();
    }

    public function test_view_only_user_can_view_products(): void
    {
        Sanctum::actingAs($this->viewOnlyUser);

        $response = $this->getJson('/api/v1/products');

        $response->assertOk();
    }

    public function test_view_only_user_cannot_create_product(): void
    {
        Sanctum::actingAs($this->viewOnlyUser);

        $response = $this->postJson('/api/v1/products', [
            'name' => 'New Product',
            'category_id' => $this->product->category_id,
            'unit' => 'piece',
            'purchase_price' => 100,
            'sale_price' => 150,
        ]);

        $response->assertForbidden()
            ->assertJson(['message' => 'ليس لديك صلاحية كاملة لإضافة منتجات في هذا الفرع']);
    }

    public function test_full_access_user_can_create_product(): void
    {
        Sanctum::actingAs($this->fullAccessUser);

        $response = $this->postJson('/api/v1/products', [
            'name' => 'New Product',
            'category_id' => $this->product->category_id,
            'unit' => 'piece',
            'purchase_price' => 100,
            'sale_price' => 150,
            'min_stock' => 10,
            'initial_stock' => [
                [
                    'branch_id' => $this->branch1->id,
                    'quantity' => 50,
                ],
            ],
        ]);

        $response->assertCreated();
    }

    public function test_admin_can_create_product(): void
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->postJson('/api/v1/products', [
            'name' => 'Admin Product',
            'category_id' => $this->product->category_id,
            'unit' => 'piece',
            'purchase_price' => 100,
            'sale_price' => 150,
            'min_stock' => 10,
            'initial_stock' => [
                [
                    'branch_id' => $this->branch1->id,
                    'quantity' => 100,
                ],
            ],
        ]);

        $response->assertCreated();
    }

    public function test_view_only_user_cannot_update_product(): void
    {
        Sanctum::actingAs($this->viewOnlyUser);

        $response = $this->putJson("/api/v1/products/{$this->product->id}", [
            'name' => 'Updated Product',
            'category_id' => $this->product->category_id,
            'unit' => 'piece',
            'purchase_price' => 120,
            'sale_price' => 180,
        ]);

        $response->assertForbidden();
    }

    public function test_full_access_user_can_update_product(): void
    {
        Sanctum::actingAs($this->fullAccessUser);

        $response = $this->putJson("/api/v1/products/{$this->product->id}", [
            'name' => 'Updated Product',
            'category_id' => $this->product->category_id,
            'unit' => 'piece',
            'purchase_price' => 120,
            'sale_price' => 180,
        ]);

        $response->assertOk();
    }

    public function test_regular_user_cannot_delete_product(): void
    {
        Sanctum::actingAs($this->fullAccessUser);

        $response = $this->deleteJson("/api/v1/products/{$this->product->id}");

        $response->assertForbidden()
            ->assertJson(['message' => 'فقط المدير يمكنه حذف المنتجات']);
    }

    public function test_admin_can_delete_product(): void
    {
        // Create product without stock (manually)
        $product = Product::create([
            'category_id' => $this->product->category_id,
            'name' => 'Delete Test Product',
            'code' => 'DEL001',
            'unit' => 'piece',
            'purchase_price' => 100,
            'sale_price' => 150,
        ]);

        Sanctum::actingAs($this->adminUser);

        $response = $this->deleteJson("/api/v1/products/{$product->id}");

        $response->assertOk();
    }

    // ========================================================================
    // IssueVoucherController - Branch Permission Tests
    // ========================================================================

    public function test_admin_can_view_all_vouchers(): void
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson('/api/v1/issue-vouchers');

        $response->assertOk();
    }

    public function test_user_can_only_view_branch_vouchers(): void
    {
        // Create customer manually
        $customer = Customer::create([
            'name' => 'Test Customer',
            'code' => 'CUST001',
            'phone' => '123456789',
            'balance' => 0,
        ]);

        // Create voucher in branch1 manually
        $voucher1 = IssueVoucher::create([
            'voucher_number' => 'ISS001',
            'branch_id' => $this->branch1->id,
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'issue_date' => now(),
            'total_amount' => 100,
            'status' => 'completed',
            'created_by' => $this->viewOnlyUser->id,
        ]);

        // Create voucher in branch2 manually
        $voucher2 = IssueVoucher::create([
            'voucher_number' => 'ISS002',
            'branch_id' => $this->branch2->id,
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'issue_date' => now(),
            'total_amount' => 200,
            'status' => 'completed',
            'created_by' => $this->adminUser->id,
        ]);

        Sanctum::actingAs($this->viewOnlyUser);

        $response = $this->getJson('/api/v1/issue-vouchers');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_view_only_user_cannot_create_voucher(): void
    {
        $customer = Customer::create([
            'name' => 'Test Customer 2',
            'code' => 'CUST002',
            'phone' => '987654321',
            'balance' => 0,
        ]);

        Sanctum::actingAs($this->viewOnlyUser);

        $response = $this->postJson('/api/v1/issue-vouchers', [
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'branch_id' => $this->branch1->id,
            'issue_date' => now()->toDateString(),
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 5,
                    'unit_price' => 100,
                ],
            ],
        ]);

        $response->assertForbidden()
            ->assertJson(['message' => 'ليس لديك صلاحية كاملة لإنشاء أذونات صرف في هذا الفرع']);
    }

    public function test_full_access_user_can_create_voucher(): void
    {
        $customer = Customer::create([
            'name' => 'Test Customer 3',
            'code' => 'CUST003',
            'phone' => '555666777',
            'balance' => 0,
        ]);

        // Add stock first
        $this->product->branchStocks()->create([
            'branch_id' => $this->branch1->id,
            'current_stock' => 100,
        ]);

        Sanctum::actingAs($this->fullAccessUser);

        $response = $this->postJson('/api/v1/issue-vouchers', [
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'branch_id' => $this->branch1->id,
            'issue_date' => now()->toDateString(),
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 5,
                    'unit_price' => 100,
                ],
            ],
        ]);

        $response->assertCreated();
    }

    // ========================================================================
    // DashboardController - Branch Filtering Tests
    // ========================================================================

    public function test_admin_can_view_all_branches_dashboard(): void
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson('/api/v1/dashboard');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'total_products',
                    'total_branches',
                    'total_stock_value',
                    'branch_name',
                ],
            ]);
    }

    public function test_user_sees_only_branch_dashboard(): void
    {
        Sanctum::actingAs($this->viewOnlyUser);

        $response = $this->getJson('/api/v1/dashboard');

        $response->assertOk()
            ->assertJsonPath('data.branch_id', $this->branch1->id)
            ->assertJsonPath('data.branch_name', 'Branch 1');
    }

    public function test_user_without_branch_cannot_access_dashboard(): void
    {
        Sanctum::actingAs($this->noAccessUser);

        $response = $this->getJson('/api/v1/dashboard');

        $response->assertForbidden()
            ->assertJson(['message' => 'لم يتم تعيين فرع للمستخدم']);
    }

    // ========================================================================
    // Edge Cases & Security Tests
    // ========================================================================

    public function test_user_cannot_access_other_branch_data(): void
    {
        // Create customer and voucher in branch2 manually
        $customer = Customer::create([
            'name' => 'Other Branch Customer',
            'code' => 'CUST004',
            'phone' => '111222333',
            'balance' => 0,
        ]);

        $voucher = IssueVoucher::create([
            'voucher_number' => 'ISS003',
            'branch_id' => $this->branch2->id,
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'issue_date' => now(),
            'total_amount' => 300,
            'status' => 'completed',
            'created_by' => $this->adminUser->id,
        ]);

        Sanctum::actingAs($this->viewOnlyUser); // Has access to branch1 only

        $response = $this->getJson("/api/v1/issue-vouchers/{$voucher->id}");

        $response->assertForbidden()
            ->assertJson(['message' => 'ليس لديك صلاحية لعرض هذا الإذن']);
    }

    public function test_admin_can_access_any_branch_data(): void
    {
        $customer = Customer::create([
            'name' => 'Admin Access Customer',
            'code' => 'CUST005',
            'phone' => '444555666',
            'balance' => 0,
        ]);

        $voucher = IssueVoucher::create([
            'voucher_number' => 'ISS004',
            'branch_id' => $this->branch2->id,
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'issue_date' => now(),
            'total_amount' => 400,
            'status' => 'completed',
            'created_by' => $this->adminUser->id,
        ]);

        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson("/api/v1/issue-vouchers/{$voucher->id}");

        $response->assertOk();
    }

    public function test_user_cannot_create_product_in_unauthorized_branch(): void
    {
        Sanctum::actingAs($this->fullAccessUser); // Has access to branch1 only

        $response = $this->postJson('/api/v1/products', [
            'name' => 'New Product',
            'category_id' => $this->product->category_id,
            'unit' => 'piece',
            'purchase_price' => 100,
            'sale_price' => 150,
            'min_stock' => 10,
            'initial_stock' => [
                [
                    'branch_id' => $this->branch2->id, // Trying to add to branch2
                    'quantity' => 50,
                ],
            ],
        ]);

        $response->assertForbidden()
            ->assertJson(['message' => 'ليس لديك صلاحية كاملة لإضافة مخزون في الفرع: Branch 2']);
    }
}
