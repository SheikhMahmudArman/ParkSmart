<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('parking_lots', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('location', 200);
            $table->integer('total_spaces')->default(0);
            $table->integer('available_spaces')->default(0);
            $table->decimal('hourly_rate', 10, 2)->default(5.00);
            $table->string('type', 50)->default('Standard');
            $table->json('features')->nullable();
            $table->timestamps();

            // MySQL indexes
            $table->index('name');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parking_lots');
    }
};
