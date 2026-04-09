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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('clt_layups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();

            $table->unique(['supplier_id', 'name']);
        });

        Schema::create('clt_layers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('layup_id')->constrained('clt_layups')->cascadeOnDelete();
            $table->unsignedInteger('layer_order');
            $table->decimal('thickness', 8, 2);
            $table->decimal('width', 8, 2);
            $table->decimal('angle', 8, 2);
            $table->timestamps();

            $table->unique(['layup_id', 'layer_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clt_layers');
        Schema::dropIfExists('clt_layups');
        Schema::dropIfExists('suppliers');
    }
};
