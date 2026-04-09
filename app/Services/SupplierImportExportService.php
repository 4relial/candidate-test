<?php

namespace App\Services;

use App\Contracts\SupplierImportExportServiceInterface;
use App\Models\Layup;
use App\Models\Supplier;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SupplierImportExportService implements SupplierImportExportServiceInterface
{
    public function export(Supplier $supplier): array
    {
        $supplier->load(['layups.layers']);

        return [
            'supplier' => [
                'id' => $supplier->id,
                'name' => $supplier->name,
                'layups' => $supplier->layups->map(function ($layup) {
                    return [
                        'id' => $layup->id,
                        'name' => $layup->name,
                        'layers' => $layup->layers->map(fn ($layer) => Arr::only($layer->toArray(), [
                            'id',
                            'layer_order',
                            'thickness',
                            'width',
                            'angle',
                        ]))->values()->all(),
                    ];
                })->values()->all(),
            ],
        ];
    }

    public function analyzeImport(Supplier $supplier, array $payload): array
    {
        $normalizedPayload = $this->normalizePayload($payload);
        $supplier->load(['layups.layers']);

        $conflicts = [];

        foreach ($normalizedPayload['layups'] as $layupData) {
            $existingLayup = $supplier->layups->firstWhere('name', $layupData['name']);

            if (! $existingLayup) {
                continue;
            }

            $layerConflicts = [];

            foreach ($layupData['layers'] as $incomingLayer) {
                $existingLayer = $existingLayup->layers->firstWhere('layer_order', $incomingLayer['layer_order']);

                if (! $existingLayer) {
                    continue;
                }

                $existingValues = $this->comparableLayerValues($existingLayer->toArray());
                $incomingValues = $this->comparableLayerValues($incomingLayer);

                if ($existingValues !== $incomingValues) {
                    $layerConflicts[] = [
                        'layer_order' => $incomingLayer['layer_order'],
                        'existing' => $existingValues,
                        'incoming' => $incomingValues,
                    ];
                }
            }

            if ($layerConflicts === []) {
                continue;
            }

            $conflicts[] = [
                'id' => $layupData['import_key'],
                'layup_name' => $layupData['name'],
                'message' => 'Differences were found in one or more existing layers.',
                'existing' => [
                    'id' => $existingLayup->id,
                    'name' => $existingLayup->name,
                    'layers' => $existingLayup->layers->map(fn ($layer) => Arr::only($layer->toArray(), [
                        'id',
                        'layer_order',
                        'thickness',
                        'width',
                        'angle',
                    ]))->values()->all(),
                ],
                'incoming' => $layupData,
                'layer_conflicts' => $layerConflicts,
            ];
        }

        return [
            'payload' => $normalizedPayload,
            'conflicts' => $conflicts,
        ];
    }

    public function import(Supplier $supplier, array $payload, array $decisions = [], string $defaultStrategy = 'overwrite'): array
    {
        $normalizedPayload = $this->normalizePayload($payload);

        $summary = [
            'created_layups' => 0,
            'duplicated_layups' => 0,
            'updated_layups' => 0,
            'created_layers' => 0,
            'updated_layers' => 0,
            'skipped_conflicts' => 0,
        ];

        DB::transaction(function () use ($supplier, $normalizedPayload, $decisions, $defaultStrategy, &$summary): void {
            foreach ($normalizedPayload['layups'] as $layupData) {
                $existingLayup = $supplier->layups()->where('name', $layupData['name'])->first();

                if (! $existingLayup) {
                    $newLayup = $supplier->layups()->create([
                        'name' => $layupData['name'],
                    ]);

                    $summary['created_layups']++;
                    $this->syncLayers($newLayup, $layupData['layers'], $summary, false);

                    continue;
                }

                $decision = $decisions[$layupData['import_key']] ?? $defaultStrategy;

                if ($decision === 'reject') {
                    throw ValidationException::withMessages([
                        'payload' => [
                            "Conflict found on layup {$layupData['name']}. Import rejected.",
                        ],
                    ]);
                }

                if ($decision === 'skip') {
                    $summary['skipped_conflicts']++;

                    continue;
                }

                if ($decision === 'duplicate') {
                    $duplicateLayup = $supplier->layups()->create([
                        'name' => $this->duplicateLayupName($supplier, $layupData['name']),
                    ]);

                    $summary['created_layups']++;
                    $summary['duplicated_layups']++;
                    $this->syncLayers($duplicateLayup, $layupData['layers'], $summary, false);

                    continue;
                }

                $layupWasChanged = $this->syncLayers($existingLayup, $layupData['layers'], $summary, true);

                if ($layupWasChanged) {
                    $summary['updated_layups']++;
                }
            }
        });

        return $summary;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function normalizePayload(array $payload): array
    {
        $source = $payload;

        if (is_array(Arr::get($payload, 'supplier')) && is_array(Arr::get($payload, 'supplier.layups'))) {
            $source = (array) $payload['supplier'];
        }

        $layups = [];

        foreach ((array) Arr::get($source, 'layups', []) as $index => $layupData) {
            $name = trim((string) Arr::get($layupData, 'name'));

            if ($name === '') {
                continue;
            }

            $layers = [];

            foreach ((array) Arr::get($layupData, 'layers', []) as $layerData) {
                $layerOrder = (int) Arr::get($layerData, 'layer_order');

                if ($layerOrder < 1) {
                    continue;
                }

                $layers[] = [
                    'layer_order' => $layerOrder,
                    'thickness' => (float) Arr::get($layerData, 'thickness', 0),
                    'width' => (float) Arr::get($layerData, 'width', 0),
                    'angle' => (float) Arr::get($layerData, 'angle', 0),
                ];
            }

            usort($layers, fn (array $left, array $right) => $left['layer_order'] <=> $right['layer_order']);

            $layups[] = [
                'import_key' => (string) $index,
                'name' => $name,
                'layers' => $layers,
            ];
        }

        return [
            'layups' => $layups,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $layers
     * @param  array<string, int>  $summary
     */
    private function syncLayers(Layup $layup, array $layers, array &$summary, bool $overwriteExisting): bool
    {
        $changed = false;

        foreach ($layers as $incomingLayer) {
            $existingLayer = $layup->layers()->where('layer_order', $incomingLayer['layer_order'])->first();

            if (! $existingLayer) {
                $layup->layers()->create($incomingLayer);
                $summary['created_layers']++;
                $changed = true;

                continue;
            }

            if (! $overwriteExisting) {
                continue;
            }

            $existingValues = $this->comparableLayerValues($existingLayer->toArray());
            $incomingValues = $this->comparableLayerValues($incomingLayer);

            if ($existingValues === $incomingValues) {
                continue;
            }

            $existingLayer->update($incomingValues + [
                'layer_order' => $incomingLayer['layer_order'],
            ]);
            $summary['updated_layers']++;
            $changed = true;
        }

        return $changed;
    }

    /**
     * @param  array<string, mixed>  $layer
     * @return array<string, float>
     */
    private function comparableLayerValues(array $layer): array
    {
        return [
            'thickness' => (float) Arr::get($layer, 'thickness', 0),
            'width' => (float) Arr::get($layer, 'width', 0),
            'angle' => (float) Arr::get($layer, 'angle', 0),
        ];
    }

    private function duplicateLayupName(Supplier $supplier, string $baseName): string
    {
        $candidate = $baseName.' (imported)';
        $suffix = 2;

        while ($supplier->layups()->where('name', $candidate)->exists()) {
            $candidate = $baseName." (imported {$suffix})";
            $suffix++;
        }

        return $candidate;
    }
}
