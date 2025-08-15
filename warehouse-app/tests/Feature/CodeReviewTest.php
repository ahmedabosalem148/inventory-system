<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\InventoryService;
use InvalidArgumentException;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CodeReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_service_validates_max_quantity()
    {
        $service = app(InventoryService::class);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('الكمية كبيرة جداً');
        
        $service->add(1, 1, 200000);
    }

    public function test_inventory_service_validates_zero_quantity()
    {
        $service = app(InventoryService::class);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('الكمية يجب أن تكون أكبر من صفر');
        
        $service->add(1, 1, 0);
    }

    public function test_admin_dashboard_supports_pagination()
    {
        $response = $this->get('/admin/dashboard?page=1');
        
        // Should not error out (would error if pagination not supported)
        $response->assertStatus(200);
    }
}
