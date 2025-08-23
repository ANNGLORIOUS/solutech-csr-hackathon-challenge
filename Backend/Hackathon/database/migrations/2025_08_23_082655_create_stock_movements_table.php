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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->enum('from_role', ['manufacturer', 'distributor', 'van_rep']);
            $table->foreignId('from_user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->enum('to_role', ['manufacturer', 'distributor', 'van_rep']);
            $table->foreignId('to_user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->integer('quantity');
            $table->date('date');
            $table->timestamps();

            $table->index(['date', 'from_role', 'to_role']);
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};