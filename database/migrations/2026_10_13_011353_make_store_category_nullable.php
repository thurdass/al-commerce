<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table): void {
            $table->string('category')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('stores')->whereNull('category')->update(['category' => '']);

        Schema::table('stores', function (Blueprint $table): void {
            $table->string('category')->nullable(false)->change();
        });
    }
};
