<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('layups') && ! Schema::hasTable('clt_layups')) {
            Schema::rename('layups', 'clt_layups');
        }

        if (Schema::hasTable('layers') && ! Schema::hasTable('clt_layers')) {
            Schema::rename('layers', 'clt_layers');
        }

        $supplierColumnsToDrop = array_keys(array_filter([
            'code' => Schema::hasTable('suppliers') && Schema::hasColumn('suppliers', 'code'),
            'address' => Schema::hasTable('suppliers') && Schema::hasColumn('suppliers', 'address'),
            'notes' => Schema::hasTable('suppliers') && Schema::hasColumn('suppliers', 'notes'),
        ]));

        if ($supplierColumnsToDrop !== []) {
            Schema::table('suppliers', function (Blueprint $table) use ($supplierColumnsToDrop): void {
                $table->dropColumn($supplierColumnsToDrop);
            });
        }

        if (Schema::hasTable('clt_layups') && Schema::hasColumn('clt_layups', 'description')) {
            Schema::table('clt_layups', function (Blueprint $table): void {
                $table->dropColumn('description');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('suppliers')) {
            Schema::table('suppliers', function (Blueprint $table): void {
                if (! Schema::hasColumn('suppliers', 'code')) {
                    $table->string('code')->nullable();
                }

                if (! Schema::hasColumn('suppliers', 'address')) {
                    $table->string('address')->nullable();
                }

                if (! Schema::hasColumn('suppliers', 'notes')) {
                    $table->text('notes')->nullable();
                }
            });
        }

        if (Schema::hasTable('clt_layups') && ! Schema::hasColumn('clt_layups', 'description')) {
            Schema::table('clt_layups', function (Blueprint $table): void {
                $table->text('description')->nullable();
            });
        }

        if (Schema::hasTable('clt_layers') && ! Schema::hasTable('layers')) {
            Schema::rename('clt_layers', 'layers');
        }

        if (Schema::hasTable('clt_layups') && ! Schema::hasTable('layups')) {
            Schema::rename('clt_layups', 'layups');
        }
    }
};
