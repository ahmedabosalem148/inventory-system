<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class PhoneValidationTest extends TestCase
{
    /**
     * Valid Egyptian phone numbers
     */
    public static function validEgyptianPhones(): array
    {
        return [
            ['01012345678'],      // Vodafone
            ['01112345678'],      // Etisalat
            ['01212345678'],      // Orange
            ['01512345678'],      // WE
            ['+201012345678'],    // With country code
            ['+201112345678'],    // With country code
        ];
    }

    /**
     * Invalid Egyptian phone numbers
     */
    public static function invalidEgyptianPhones(): array
    {
        return [
            ['0123456789'],       // Invalid operator code
            ['01412345678'],      // Invalid operator 014
            ['0101234567'],       // Too short
            ['010123456789'],     // Too long
            ['1012345678'],       // Missing leading 0
            ['+2201012345678'],   // Extra digit in country code
            ['01012345abc'],      // Contains letters
            ['010-1234-5678'],    // Contains dashes
            ['010 1234 5678'],    // Contains spaces
            ['+1 010 123 4567'],  // Wrong country code format
        ];
    }

    /**
     * @dataProvider validEgyptianPhones
     */
    public function test_customer_request_accepts_valid_egyptian_phone(string $phone): void
    {
        $request = new StoreCustomerRequest();
        
        $data = [
            'name' => 'Test Customer',
            'customer_type' => 'INDIVIDUAL',
            'phone' => $phone
        ];
        
        $validator = Validator::make($data, $request->rules());
        
        $this->assertFalse($validator->fails(), 
            "Phone {$phone} should be valid. Errors: " . json_encode($validator->errors()->toArray())
        );
    }

    /**
     * @dataProvider invalidEgyptianPhones
     */
    public function test_customer_request_rejects_invalid_egyptian_phone(string $phone): void
    {
        $request = new StoreCustomerRequest();
        
        $data = [
            'name' => 'Test Customer',
            'customer_type' => 'INDIVIDUAL',
            'phone' => $phone
        ];
        
        $validator = Validator::make($data, $request->rules());
        
        $this->assertTrue($validator->fails(), "Phone {$phone} should be invalid");
        $this->assertArrayHasKey('phone', $validator->errors()->toArray());
    }

    /**
     * @dataProvider validEgyptianPhones
     */
    public function test_supplier_request_accepts_valid_egyptian_phone(string $phone): void
    {
        $request = new StoreSupplierRequest();
        
        $data = [
            'name' => 'Test Supplier',
            'phone' => $phone
        ];
        
        $validator = Validator::make($data, $request->rules());
        
        // Should not have phone error
        $errors = $validator->errors()->toArray();
        $this->assertArrayNotHasKey('phone', $errors, 
            "Phone {$phone} should be valid. Phone errors: " . json_encode($errors['phone'] ?? [])
        );
    }

    /**
     * @dataProvider invalidEgyptianPhones
     */
    public function test_supplier_request_rejects_invalid_egyptian_phone(string $phone): void
    {
        $request = new StoreSupplierRequest();
        
        $data = [
            'name' => 'Test Supplier',
            'phone' => $phone
        ];
        
        $validator = Validator::make($data, $request->rules());
        
        $this->assertTrue($validator->fails(), "Phone {$phone} should be invalid");
        $this->assertArrayHasKey('phone', $validator->errors()->toArray());
    }

    public function test_update_customer_request_validates_phone(): void
    {
        $request = new UpdateCustomerRequest();
        
        // Valid phone
        $data = ['phone' => '01012345678'];
        $validator = Validator::make($data, $request->rules());
        $this->assertArrayNotHasKey('phone', $validator->errors()->toArray());
        
        // Invalid phone
        $data = ['phone' => '0123456789'];
        $validator = Validator::make($data, $request->rules());
        $this->assertArrayHasKey('phone', $validator->errors()->toArray());
    }

    public function test_update_supplier_request_validates_phone(): void
    {
        $request = new UpdateSupplierRequest();
        
        // Valid phone
        $data = ['name' => 'Test', 'phone' => '+201012345678'];
        $validator = Validator::make($data, $request->rules());
        $this->assertArrayNotHasKey('phone', $validator->errors()->toArray());
        
        // Invalid phone
        $data = ['name' => 'Test', 'phone' => '123456'];
        $validator = Validator::make($data, $request->rules());
        $this->assertArrayHasKey('phone', $validator->errors()->toArray());
    }

    public function test_arabic_error_message_for_invalid_phone(): void
    {
        $request = new StoreCustomerRequest();
        
        $data = [
            'name' => 'Test Customer',
            'customer_type' => 'INDIVIDUAL',
            'phone' => 'invalid'
        ];
        
        $validator = Validator::make($data, $request->rules(), $request->messages());
        
        $this->assertTrue($validator->fails());
        $phoneErrors = $validator->errors()->get('phone');
        $this->assertNotEmpty($phoneErrors);
        $this->assertStringContainsString('صيغة', $phoneErrors[0]); // Arabic message
    }

    public function test_phone_can_be_nullable_in_supplier(): void
    {
        $request = new StoreSupplierRequest();
        
        $data = [
            'name' => 'Test Supplier',
            'phone' => null
        ];
        
        $validator = Validator::make($data, $request->rules());
        
        $this->assertArrayNotHasKey('phone', $validator->errors()->toArray());
    }
}
