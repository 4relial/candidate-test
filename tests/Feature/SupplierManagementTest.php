<?php

namespace Tests\Feature;

use App\Models\Layer;
use App\Models\Layup;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_supplier_layup_and_layer(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('suppliers.store'), [
                'name' => 'PT Kayu Nusantara',
            ])
            ->assertRedirect();

        $supplier = Supplier::first();

        $this->actingAs($user)
            ->post(route('suppliers.layups.store', $supplier), [
                'name' => 'Standard CLT',
            ])
            ->assertRedirect();

        $layup = Layup::first();

        $this->actingAs($user)
            ->post(route('suppliers.layups.layers.store', [$supplier, $layup]), [
                'layer_order' => 1,
                'thickness' => 35,
                'width' => 120,
                'angle' => 0,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('suppliers', ['name' => 'PT Kayu Nusantara']);
        $this->assertDatabaseHas('clt_layups', ['name' => 'Standard CLT']);
        $this->assertDatabaseHas('clt_layers', ['layer_order' => 1, 'thickness' => 35.0]);
    }

    public function test_export_returns_supplier_with_nested_layups_and_layers(): void
    {
        $user = User::factory()->create();
        $supplier = Supplier::factory()
            ->has(Layup::factory()->has(Layer::factory()->count(2)))
            ->create();

        $response = $this->actingAs($user)
            ->get(route('suppliers.export', $supplier));

        $response->assertOk()
            ->assertJsonPath('supplier.id', $supplier->id)
            ->assertJsonCount(1, 'supplier.layups')
            ->assertJsonCount(2, 'supplier.layups.0.layers');
    }

    public function test_import_with_overwrite_updates_existing_conflicting_layer(): void
    {
        $user = User::factory()->create();
        $supplier = Supplier::factory()->create(['name' => 'PT Import']);
        $layup = Layup::factory()->for($supplier)->create(['name' => 'Existing Layup']);
        $layer = Layer::factory()->for($layup)->create([
            'layer_order' => 1,
            'thickness' => 20,
            'width' => 100,
            'angle' => 0,
        ]);

        $payload = [
            'layups' => [[
                'name' => 'Existing Layup',
                'layers' => [[
                    'layer_order' => 1,
                    'thickness' => 30,
                    'width' => 140,
                    'angle' => 45,
                ]],
            ]],
        ];

        $this->actingAs($user)
            ->post(route('suppliers.import', $supplier), [
                'strategy' => 'overwrite',
                'payload' => json_encode($payload, JSON_PRETTY_PRINT),
            ])
            ->assertRedirect();

        $layer->refresh();

        $this->assertSame(30.0, (float) $layer->thickness);
        $this->assertSame(140.0, (float) $layer->width);
        $this->assertSame(45.0, (float) $layer->angle);
    }

    public function test_import_with_reject_keeps_existing_data_when_conflict_found(): void
    {
        $user = User::factory()->create();
        $supplier = Supplier::factory()->create();
        $layup = Layup::factory()->for($supplier)->create(['name' => 'Reject Layup']);
        $layer = Layer::factory()->for($layup)->create([
            'layer_order' => 1,
            'thickness' => 25,
            'width' => 110,
            'angle' => 0,
        ]);

        $payload = [
            'supplier' => [
                'name' => 'Imported Supplier',
                'layups' => [[
                    'name' => 'Reject Layup',
                    'layers' => [[
                        'layer_order' => 1,
                        'thickness' => 99,
                        'width' => 222,
                        'angle' => 90,
                    ]],
                ]],
            ],
        ];

        $this->actingAs($user)
            ->from(route('suppliers.show', $supplier))
            ->post(route('suppliers.import', $supplier), [
                'payload' => json_encode($payload, JSON_PRETTY_PRINT),
            ])
            ->assertRedirect(route('suppliers.import.review', $supplier));

        $this->actingAs($user)
            ->post(route('suppliers.import.resolve', $supplier), [
                'action' => 'reject',
            ])
            ->assertRedirect(route('suppliers.show', $supplier));

        $layer->refresh();

        $this->assertSame(25.0, (float) $layer->thickness);
        $this->assertSame(110.0, (float) $layer->width);
        $this->assertSame(0.0, (float) $layer->angle);
    }

    public function test_import_accepts_exported_supplier_json_structure_without_conflicts(): void
    {
        $user = User::factory()->create();
        $supplier = Supplier::factory()->create();

        $payload = [
            'supplier' => [
                'name' => 'External Supplier',
                'layups' => [[
                    'name' => 'Imported Layup',
                    'layers' => [[
                        'layer_order' => 1,
                        'thickness' => 40,
                        'width' => 150,
                        'angle' => 0,
                    ]],
                ]],
            ],
        ];

        $this->actingAs($user)
            ->post(route('suppliers.import', $supplier), [
                'payload' => json_encode($payload, JSON_PRETTY_PRINT),
            ])
            ->assertRedirect(route('suppliers.show', $supplier));

        $this->assertDatabaseHas('clt_layups', [
            'supplier_id' => $supplier->id,
            'name' => 'Imported Layup',
        ]);

        $this->assertDatabaseHas('clt_layers', [
            'layer_order' => 1,
            'thickness' => 40.0,
            'width' => 150.0,
            'angle' => 0.0,
        ]);
    }

    public function test_import_updates_existing_layup_directly_when_no_actual_conflict_exists(): void
    {
        $user = User::factory()->create();
        $supplier = Supplier::factory()->create();
        $layup = Layup::factory()->for($supplier)->create(['name' => 'Same Layup']);

        Layer::factory()->for($layup)->create([
            'layer_order' => 1,
            'thickness' => 20,
            'width' => 100,
            'angle' => 0,
        ]);

        $payload = [
            'supplier' => [
                'name' => 'External Supplier',
                'layups' => [[
                    'name' => 'Same Layup',
                    'layers' => [
                        [
                            'layer_order' => 1,
                            'thickness' => 20,
                            'width' => 100,
                            'angle' => 0,
                        ],
                        [
                            'layer_order' => 2,
                            'thickness' => 25,
                            'width' => 120,
                            'angle' => 90,
                        ],
                    ],
                ]],
            ],
        ];

        $this->actingAs($user)
            ->post(route('suppliers.import', $supplier), [
                'payload' => json_encode($payload, JSON_PRETTY_PRINT),
            ])
            ->assertRedirect(route('suppliers.show', $supplier));

        $this->assertDatabaseHas('clt_layers', [
            'layup_id' => $layup->id,
            'layer_order' => 2,
            'thickness' => 25.0,
            'width' => 120.0,
            'angle' => 90.0,
        ]);
    }

    public function test_conflicting_import_can_be_resolved_one_by_one_with_duplicate_option(): void
    {
        $user = User::factory()->create();
        $supplier = Supplier::factory()->create();
        $layup = Layup::factory()->for($supplier)->create(['name' => 'Review Layup']);

        Layer::factory()->for($layup)->create([
            'layer_order' => 1,
            'thickness' => 20,
            'width' => 100,
            'angle' => 0,
        ]);

        $payload = [
            'supplier' => [
                'name' => 'External Supplier',
                'layups' => [[
                    'name' => 'Review Layup',
                    'layers' => [[
                        'layer_order' => 1,
                        'thickness' => 33,
                        'width' => 155,
                        'angle' => 45,
                    ]],
                ]],
            ],
        ];

        $this->actingAs($user)
            ->post(route('suppliers.import', $supplier), [
                'payload' => json_encode($payload, JSON_PRETTY_PRINT),
            ])
            ->assertRedirect(route('suppliers.import.review', $supplier));

        $this->actingAs($user)
            ->get(route('suppliers.import.review', $supplier))
            ->assertOk()
            ->assertSee('Conflict Review')
            ->assertSee('Review Layup');

        $this->actingAs($user)
            ->post(route('suppliers.import.resolve', $supplier), [
                'action' => 'duplicate',
            ])
            ->assertRedirect(route('suppliers.show', $supplier));

        $this->assertDatabaseHas('clt_layups', [
            'supplier_id' => $supplier->id,
            'name' => 'Review Layup (imported)',
        ]);

        $this->assertDatabaseHas('clt_layers', [
            'layer_order' => 1,
            'thickness' => 33.0,
            'width' => 155.0,
            'angle' => 45.0,
        ]);
    }
}
