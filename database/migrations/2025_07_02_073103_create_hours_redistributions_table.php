<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hours_redistributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resource_id')->constrained('resources');
            $table->foreignId('from_client_id')->nullable()->constrained('clients');
            $table->foreignId('to_client_id')->constrained('clients');
            $table->foreignId('user_id')->constrained('users'); // Chi ha fatto l'operazione
            $table->date('redistribution_date'); // Data di riferimento delle ore
            $table->decimal('hours', 8, 2); // Ore redistribuite
            $table->decimal('hourly_rate', 8, 2); // Tariffa oraria al momento della redistribuzione
            $table->decimal('total_value', 10, 2); // Valore totale delle ore
            $table->enum('action_type', ['return', 'transfer']); // Tipo di azione
            $table->text('notes')->nullable(); // Note aggiuntive
            $table->timestamps();

            // Indici per migliorare le performance
            $table->index(['resource_id', 'redistribution_date']);
            $table->index(['from_client_id', 'redistribution_date']);
            $table->index(['to_client_id', 'redistribution_date']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hours_redistributions');
    }
};