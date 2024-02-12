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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('sid')->unique();
            $table->foreignId('party_id')->constrained(); // fabricator
            $table->foreignId('ledger_id')->constrained();
            $table->integer('quantity')->default(0); // total of quantities
            $table->date('expected_at');
            $table->string('status')->default('issued'); // issued -> accepted, cancelled
            $table->foreignId('user_id')->constrained(); // created by user
            $table->json('log_status_time')->nullable();
            $table->boolean('queued')->default(0); // 1,0
            $table->string('tags')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_item_id')->constrained(); // each sku
            $table->foreignId('order_id')->constrained();
            $table->integer('quantity')->default(0); // each quantity
            $table->timestamps();
        });

        Schema::create('adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('sid')->unique();
            $table->enum('type', ['order', 'ready', 'demand']);
            $table->foreignId('ledger_id')->constrained();
            $table->integer('quantity')->default(0); // total of quantities
            $table->foreignId('user_id')->constrained(); // created by user
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('adjustment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_item_id')->constrained(); // each sku
            $table->foreignId('adjustment_id')->constrained();
            $table->integer('quantity')->default(0); // each quantity
            $table->timestamps();
        });

        Schema::create('demands', function (Blueprint $table) {
            $table->id();
            $table->string('sid')->unique();
            $table->foreignId('ledger_id')->constrained();
            $table->integer('quantity')->default(0); // total of quantities
            $table->date('expected_at');
            $table->float('tolerance')->default(0); // total of quantities
            $table->string('status')->default('raised'); // raised -> completed
            $table->foreignId('user_id')->constrained(); // created by user
            $table->json('log_status_time')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('demand_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_item_id')->constrained(); // each sku
            $table->foreignId('demand_id')->constrained();
            $table->integer('quantity')->default(0); // each quantity
            $table->timestamps();
        });

        Schema::create('readies', function (Blueprint $table) {
            $table->id();
            $table->string('sid')->unique();
            $table->foreignId('ledger_id')->constrained();
            $table->integer('quantity')->default(0); // total of quantities
            $table->foreignId('user_id')->constrained(); // fabricator user id
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('ready_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_item_id')->constrained(); // each sku
            $table->foreignId('ready_id')->constrained();
            $table->integer('quantity')->default(0); // each quantity
            $table->timestamps();
        });

        Schema::create('dispatches', function (Blueprint $table) {
            $table->id();
            $table->string('sid')->unique();
            $table->foreignId('party_id')->constrained(); // fabricator
            $table->foreignId('ledger_id')->constrained();
            $table->integer('quantity')->default(0); // total of quantities
            $table->foreignId('user_id')->constrained(); // created by user
            $table->string('tags')->nullable();
            $table->boolean('billed')->default(0); // 1,0
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('dispatch_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_item_id')->constrained(); // each sku
            $table->foreignId('dispatch_id')->constrained();
            $table->integer('quantity')->default(0); // each quantity
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('adjustments');
        Schema::dropIfExists('adjustment_items');
        Schema::dropIfExists('demands');
        Schema::dropIfExists('demand_items');
        Schema::dropIfExists('readies');
        Schema::dropIfExists('ready_items');
        Schema::dropIfExists('dispatches');
        Schema::dropIfExists('dispatches_items');
    }
};
