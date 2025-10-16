<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('products')) {
            return; // products table will be created by previous migration
        }

        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'incoming_stock')) {
                $table->integer('incoming_stock')->default(0)->after('stock');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('products')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'incoming_stock')) {
                $table->dropColumn('incoming_stock');
            }
        });
    }
};