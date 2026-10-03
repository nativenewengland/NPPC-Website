<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prisoner_cases', function (Blueprint $table) {
            $table->unsignedInteger('documented_imprisoned_for_days')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('prisoner_cases', function (Blueprint $table) {
            $table->dropColumn('documented_imprisoned_for_days');
        });
    }
};
