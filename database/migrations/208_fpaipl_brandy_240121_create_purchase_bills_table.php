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
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->date('doc_date');
            $table->string('doc_id')->nullable();
            $table->integer('quantity')->default(0);
            $table->integer('tax')->default(0);
            $table->integer('total')->default(0);
            $table->text('tags')->nullable();
            $table->string('status')->default('received'); // received, stocked, paid or cancelled
            $table->foreignId('party_id')->constrained(); // fabricator
            $table->timestamps();
        });

        Schema::create('purchase_dispatch', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained(); // Accepted by
            $table->foreignId('purchase_id')->constrained();
            $table->foreignId('dispatch_id')->constrained();
            $table->foreignId('ledger_id')->constrained();
            $table->unique(['purchase_id', 'dispatch_id', 'user_id']);
            $table->timestamps();
        });

        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained();
            $table->foreignId('stock_item_id')->constrained();
            $table->foreignId('group_id')->constrained('purchase_dispatch');
            $table->foreignId('dispatch_item_id')->constrained('dispatch_items');
            $table->integer('rate')->default(0);
            $table->integer('quantity')->default(0);
            $table->integer('amount')->default(0);
            $table->string('status')->default('received'); // received, stocked or rejected
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_dispatch');
        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchases');
    }
};
