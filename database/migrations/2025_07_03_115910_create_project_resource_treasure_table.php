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
        Schema::create('project_resource_treasure', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('resource_id')->constrained()->onDelete('cascade');
            $table->decimal('allocated_treasure_hours', 8, 2)->default(0);
            $table->decimal('treasure_hourly_rate', 10, 2)->default(0);
            $table->decimal('treasure_total_cost', 12, 2)->default(0);
            $table->timestamps();

            // Indice unico per evitare duplicati
            $table->unique(['project_id', 'resource_id'], 'project_resource_treasure_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_resource_treasure');
    }
};
