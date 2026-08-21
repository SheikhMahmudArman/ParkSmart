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
        Schema::create('parking_spaces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parking_lot_id')
                ->constrained('parking_lots')
                ->onDelete('cascade');
            $table->string('space_number', 20);
            $table->string('status', 20)->default('Available');
            $table->string('type', 50)->default('Standard');
            $table->timestamps();

            // MySQL indexes
            $table->index('status');
            $table->index('space_number');
            $table->unique(['parking_lot_id', 'space_number']);
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parking_spaces');
    }
};
