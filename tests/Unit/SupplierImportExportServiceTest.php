<?php

namespace Tests\Unit;

use App\Contracts\SupplierImportExportServiceInterface;
use App\Models\Layer;
use App\Models\Layup;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierImportExportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_analyze_import_only_flags_actual_layer_differences(): void
    {
        $supplier = Supplier::factory()->create();
        $layup = Layup::factory()->for($supplier)->create(['name' => 'Core Layup']);

        Layer::factory()->for($layup)->create([
            'layer_order' => 1,
            'thickness' => 20,
            'width' => 100,
            'angle' => 0,
        ]);

        $service = app(SupplierImportExportServiceInterface::class);

        $analysis = $service->analyzeImport($supplier, [
            'supplier' => [
                'layups' => [[
                    'name' => 'Core Layup',
                    'layers' => [
                        [
                            'layer_order' => 1,
                            'thickness' => 20,
                            'width' => 100,
                            'angle' => 0,
                        ],
                        [
                            'layer_order' => 2,
                            'thickness' => 30,
                            'width' => 120,
                            'angle' => 90,
                        ],
                    ],
                ]],
            ],
        ]);

        $this->assertCount(0, $analysis['conflicts']);
        $this->assertCount(1, $analysis['payload']['layups']);
    }

    public function test_import_can_duplicate_conflicting_layup_with_suffix(): void
    {
        $supplier = Supplier::factory()->create();
        $layup = Layup::factory()->for($supplier)->create(['name' => 'Panel A']);

        Layer::factory()->for($layup)->create([
            'layer_order' => 1,
            'thickness' => 18,
            'width' => 95,
            'angle' => 0,
        ]);

        $service = app(SupplierImportExportServiceInterface::class);

        $summary = $service->import(
            $supplier,
            [
                'supplier' => [
                    'layups' => [[
                        'name' => 'Panel A',
                        'layers' => [[
                            'layer_order' => 1,
                            'thickness' => 28,
                            'width' => 145,
                            'angle' => 45,
                        ]],
                    ]],
                ],
            ],
            ['0' => 'duplicate']
        );

        $this->assertSame(1, $summary['duplicated_layups']);
        $this->assertDatabaseHas('clt_layups', [
            'supplier_id' => $supplier->id,
            'name' => 'Panel A (imported)',
        ]);
    }
}
