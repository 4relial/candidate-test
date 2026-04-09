<?php

namespace App\Contracts;

use App\Models\Supplier;

interface SupplierImportExportServiceInterface
{
    /**
     * @return array<string, mixed>
     */
    public function export(Supplier $supplier): array;

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function analyzeImport(Supplier $supplier, array $payload): array;

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, string>  $decisions
     * @return array<string, mixed>
     */
    public function import(Supplier $supplier, array $payload, array $decisions = [], string $defaultStrategy = 'overwrite'): array;
}
