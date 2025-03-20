<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('projects', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->text('description')->nullable();
        $table->foreignId('client_id')->constrained()->onDelete('cascade');
        $table->json('cost_steps')->nullable();
        $table->decimal('total_cost', 12, 2)->default(0);
        $table->date('start_date')->nullable();
        $table->date('end_date')->nullable();
        $table->enum('status', ['pending', 'in_progress', 'completed', 'on_hold'])->default('pending');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
{
    Schema::dropIfExists('projects');
}
};
