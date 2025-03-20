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
        if (!Schema::hasColumn('project_resource', 'hours_type')) {
            Schema::table('project_resource', function (Blueprint $table) {
                $table->enum('hours_type', ['standard', 'extra'])->default('standard')->after('hours');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        if (Schema::hasColumn('project_resource', 'hours_type')) {
            Schema::table('project_resource', function (Blueprint $table) {
                $table->dropColumn('hours_type');
            });
        }
    }
};