<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Laravel 9 não adiciona foreign keys em tabelas SQLite existentes via Schema.
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::statement('ALTER TABLE products ADD COLUMN category_id INTEGER REFERENCES categories(id) ON DELETE SET NULL');

            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
        });
    }

    public function down()
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('category_id');
            });

            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
        });
    }
};
