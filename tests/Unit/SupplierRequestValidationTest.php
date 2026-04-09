<?php

namespace Tests\Unit;

use App\Http\Requests\SupplierRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class SupplierRequestValidationTest extends TestCase
{
    public function test_supplier_name_is_required(): void
    {
        $validator = Validator::make([], (new SupplierRequest())->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->messages());
    }

    public function test_supplier_name_must_not_exceed_255_characters(): void
    {
        $validator = Validator::make([
            'name' => str_repeat('A', 256),
        ], (new SupplierRequest())->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->messages());
    }

    public function test_supplier_name_passes_when_valid(): void
    {
        $validator = Validator::make([
            'name' => 'PT Kayu Nusantara',
        ], (new SupplierRequest())->rules());

        $this->assertFalse($validator->fails());
    }
}
