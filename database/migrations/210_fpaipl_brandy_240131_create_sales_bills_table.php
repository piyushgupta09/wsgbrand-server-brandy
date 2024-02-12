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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->date('doc_date');
            $table->string('doc_id')->nullable();
            $table->integer('quantity')->default(0);
            $table->integer('tax')->default(0);
            $table->integer('total')->default(0);
            $table->text('tags')->nullable();
            $table->string('status')->default('received'); // received, stocked, paid or cancelled
            $table->json('customer')->nullable();
            $table->timestamps();
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->decimal('mrp', $precision = 8, $scale = 2)->default(0.00);
            $table->decimal('rate', $precision = 8, $scale = 2)->default(0.00);
            $table->integer('quantity')->default(0); // each slu's pcs quantity
            $table->float('amount')->default(0); // total - tax
            $table->float('tax')->default(0); // total * (taxrate/100)
            $table->float('total')->default(0); // quantity * rate
            $table->foreignId('sale_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        // This is wsg OrderProduct
        Schema::create('sos', function (Blueprint $table) {
            $table->id();
            $table->integer('skus')->nullable(); // sku's count
            $table->integer('quantity')->nullable(); // quantity of every sku
            $table->float('amount')->default(0); // total - tax
            $table->float('tax')->default(0); // total * (taxrate/100)
            $table->float('total')->default(0); // quantity * rate
            $table->string('oid')->nullable(); // wsg order id
            $table->string('suborder_id')->nullable(); // wsg Order id + index
            $table->foreignId('stock_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('sale_id')->nullable()->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        // This is wsg OrderItem
        Schema::create('so_items', function (Blueprint $table) {
            $table->id();
            $table->decimal('mrp', $precision = 8, $scale = 2)->default(0.00);
            $table->decimal('rate', $precision = 8, $scale = 2)->default(0.00);
            $table->integer('quantity')->default(0); // each slu's pcs quantity
            $table->float('amount')->default(0); // total - tax
            $table->float('tax')->default(0); // total * (taxrate/100)
            $table->float('total')->default(0); // quantity * rate
            $table->foreignId('so_id')->constrained()->onDelete('cascade');
            $table->foreignId('stock_item_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('product_option_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('product_range_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('sale_item_id')->nullable()->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('so_items');
        Schema::dropIfExists('sos');
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
    }
};
