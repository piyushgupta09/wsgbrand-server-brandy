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
        /**
         * Product Catelog (Using DS Api for related data using product_sid)
         */
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')->constrained()->onDelete('cascade');

            $table->string('name'); // ProductName
            $table->string('sid')->unique(); // product sid
            $table->string('sku')->unique(); // product id

            $table->integer('quantity')->default(0); // available-in-hand for sale (updated by purchase and sale)
            $table->integer('roq')->default(1); // Re-Order quantity
            $table->integer('incoming')->default(0); // order placed yet to recevied (updated by purchase)
            $table->integer('outgoing')->default(0); // reserved for sale yet to dispatch (updated by sale)
            
            // As per dsa
            $table->string('product_name')->nullable(); // product name
            $table->string('product_sid')->nullable(); // product sid 
            $table->string('product_code')->nullable(); // product code
            $table->float('mrp')->default(0); // mrp
            $table->float('price')->default(0); // sale price
            $table->integer('moq')->default(1); // Minimum order quantity

            // For ecom app
            $table->boolean('active')->default(true); // enable/disable this stock
            $table->text('note')->nullable(); // remarks for dead stock
            $table->text('tags')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('stock_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('stock_id')->constrained()->onDelete('cascade');

            $table->string('name'); // ProductName + OptionName + RangeName
            $table->string('sid')->unique(); // product sid + option sid + range sid
            $table->string('sku')->unique(); // product id + option id + range id

            $table->integer('quantity')->default(0); // available-in-hand for sale (updated by purchase and sale)

            $table->bigInteger('product_id')->nullable(); // product
            $table->string('product_name')->nullable(); // product name
            $table->string('product_sid')->nullable(); // product sid 
            $table->string('product_code')->nullable(); // product code
            $table->float('mrp')->default(0); // mrp
            $table->float('price')->default(0); // sale price
            
            $table->bigInteger('product_option_id')->nullable(); // product option
            $table->string('product_option_name')->nullable(); // product option name
            $table->string('product_option_sid')->nullable(); // product option sid

            $table->bigInteger('product_range_id')->nullable(); // product range
            $table->string('product_range_name')->nullable(); // product range name
            $table->string('product_range_sid')->nullable(); // product range sid
            
            // For ecom app
            $table->boolean('active')->default(true); // enable/disable this stock
            $table->text('note')->nullable(); // remarks for dead stock
            $table->text('tags')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_items');
        Schema::dropIfExists('stocks');
    }
};