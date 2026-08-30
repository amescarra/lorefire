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
        Schema::create('character_spells', function (Blueprint $table) {
            $table->id();
            $table->foreignId('character_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedTinyInteger('level'); // 1–9 (2E has no cantrips)
            $table->string('school')->nullable();
            $table->string('casting_time')->nullable();
            $table->string('range')->nullable();
            $table->string('components')->nullable();
            $table->string('duration')->nullable();
            $table->boolean('concentration')->default(false);
            $table->boolean('ritual')->default(false);
            $table->text('description')->nullable();
            $table->boolean('is_prepared')->default(false); // memorized
            $table->boolean('is_cast')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('character_spells');
    }
};
