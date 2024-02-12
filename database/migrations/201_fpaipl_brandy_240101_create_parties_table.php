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
        // create and show
        Schema::create('parties', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->string('sid')->unique(); // DG-001
            $table->string('type')->default('staff'); // requesting role, i.e. staff, fabricator, customer, supplier, etc.
            $table->foreignId('user_id')->nullable()->constrained();
            $table->unsignedBigInteger('monaal_id')->nullable(); // Fabricator's sid as per monaal.in
            $table->text('info')->nullable(); // additional info
            $table->text('tags')->nullable(); // searchable tags
            $table->boolean('active')->default(1); // 0 = inactive, 1 = active
            $table->string('name')->nullable(); // business name
            $table->string('business')->nullable(); // business name
            $table->string('billing_address_id')->nullable();
            $table->string('shipping_address_id')->nullable();
            $table->string('mobile')->nullable();
            $table->timestamps();
            $table->softDeletes(); // to prevent accidental deletion and to keep the old data relevent
        });

        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->string('sid')->unique(); // DG-001
            $table->string('type')->default('staff'); // requesting role, i.e. staff, fabricator, customer, supplier, etc.
            $table->foreignId('user_id')->nullable()->constrained();
            $table->text('info')->nullable(); // additional info
            $table->text('tags')->nullable(); // searchable tags
            $table->boolean('active')->default(1); // 0 = inactive, 1 = active
            $table->string('name')->nullable(); // business name
            $table->string('mobile')->nullable();
            $table->timestamps();
            $table->softDeletes(); // to prevent accidental deletion and to keep the old data relevent
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parties');
        Schema::dropIfExists('employees');
    }
};
