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
        Schema::create('ledgers', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // ledger name -> catelog name + party name
            $table->string('sid')->unique();
            $table->string('details')->nullable();

            $table->foreignId('stock_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('party_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('employee_id')->nullable()->constrained()->onDelete('cascade');
            
            $table->string('product_sid')->nullable(); // delete product code

            $table->bigInteger('min_qty')->default(0); // Alert if balance_qty < min_qty
            $table->bigInteger('max_qty')->default(0); // Alert if balance_qty > max_qty
            $table->bigInteger('fab_rate')->default(0); // Alert if balance_qty > max_qty
            $table->bigInteger('fee_rate')->default(0); // Alert if balance_qty > max_qty
            $table->bigInteger('order_cap')->default(0); // Alert if balance_qty > max_qty

            $table->bigInteger('total_order')->default(0); // Total(ready-demand) 
            $table->bigInteger('readyable_qty')->default(0); // How much he can ready
            $table->bigInteger('total_ready')->default(0); // Total(ready-demand) 
            $table->bigInteger('demandable_qty')->default(0); // How much he can demand
            $table->bigInteger('total_demand')->default(0); // Total(ready-demand) 
            $table->bigInteger('dispatchable_qty')->default(0); // How much he can dispatch
            $table->bigInteger('total_dispatch')->default(0); // Total(ready-demand) 
            $table->bigInteger('order_adj')->default(0); // order adjustment
            $table->bigInteger('ready_adj')->default(0); // ready adjustment
            $table->bigInteger('demand_adj')->default(0); // demand adjustment
            $table->bigInteger('dispatch_adj')->default(0); // demand adjustment
            $table->bigInteger('balance_qty')->default(0); // Total(order-demand) 

            $table->string('last_activity')->nullable(); // order, ready, demand, adjustment
            $table->string('tags')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['product_id', 'party_id']);
        });

        Schema::create('ledger_notigroups', function (Blueprint $table) {
            $table->id();
            $table->string('channel'); // user uuid
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('ledger_id')->constrained()->onDelete('cascade');
            $table->unique(['user_id', 'ledger_id']);
            $table->timestamps();
        });

        // Schema::create('ledger_activities', function (Blueprint $table) {
        //     $table->id();
        //     $table->string('sid')->unique();
        //     $table->boolean('adjustment')->default(0); // 1,0
        //     $table->foreignId('ledger_id')->constrained()->onDelete('cascade');
        //     $table->string('activity'); // order, ready, demand, dispatch
        //     $table->bigInteger('quantity')->default(0);
        //     $table->string('details')->nullable();
        //     $table->timestamps();
        // });

        // Schema::create('ledger_activity_items', function (Blueprint $table) {
        //     $table->id();
        //     $table->foreignId('ledger_activity_id')->constrained()->onDelete('cascade');
        //     $table->foreignId('stock_item_id')->nullable()->constrained()->onDelete('cascade');
        //     $table->foreignId('product_option_id')->nullable()->constrained()->onDelete('cascade');
        //     $table->foreignId('product_range_id')->nullable()->constrained()->onDelete('cascade');
        //     $table->bigInteger('quantity')->default(0); // each quantity
        //     $table->timestamps();
        // });

        // Schema::create('ledger_histories', function (Blueprint $table) {
        //     $table->id();
        //     $table->foreignId('ledger_id')->constrained()->onDelete('cascade');
        //     $table->bigInteger('total_order')->default(0); // Total(ready-demand) 
        //     $table->bigInteger('readyable_qty')->default(0); // How much he can ready
        //     $table->bigInteger('total_ready')->default(0); // Total(ready-demand) 
        //     $table->bigInteger('demandable_qty')->default(0); // How much he can demand
        //     $table->bigInteger('total_demand')->default(0); // Total(ready-demand) 
        //     $table->bigInteger('dispatchable_qty')->default(0); // How much he can dispatch
        //     $table->bigInteger('total_dispatch')->default(0); // Total(ready-demand) 
        //     $table->bigInteger('order_adj')->default(0); // order adjustment
        //     $table->bigInteger('ready_adj')->default(0); // ready adjustment
        //     $table->bigInteger('demand_adj')->default(0); // demand adjustment
        //     $table->bigInteger('dispatch_adj')->default(0); // demand adjustment
        //     $table->bigInteger('balance_qty')->default(0); // Total(order-demand) 
        //     $table->timestamps();
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ledgers');
        // Schema::dropIfExists('ledger_activities');
        // Schema::dropIfExists('ledger_activity_items');
        // Schema::dropIfExists('ledger_histories');
    }
};