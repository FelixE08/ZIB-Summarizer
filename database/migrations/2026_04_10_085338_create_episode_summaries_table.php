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
        Schema::create('episode_summaries', function (Blueprint $table) {
            $table->id();
            $table->string('source_title');
            $table->string('source_url')->unique();
            $table->timestamp('published_at')->nullable();
            $table->longText('transcript');
            $table->longText('summary');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('episode_summaries');
    }
};
