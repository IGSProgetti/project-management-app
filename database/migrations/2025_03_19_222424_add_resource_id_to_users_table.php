// create_migration_add_resource_id_to_users_table.php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'resource_id')) {
                $table->foreignId('resource_id')
                      ->nullable()
                      ->constrained('resources')
                      ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'resource_id')) {
                $table->dropForeignKey(['resource_id']);
                $table->dropColumn('resource_id');
            }
        });
    }
};