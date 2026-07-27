<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedback_suggestion_topic', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feedback_suggestion_id')->constrained()->cascadeOnDelete();
            $table->foreignId('suggestion_topic_id')->constrained()->cascadeOnDelete();
            $table->unique(['feedback_suggestion_id', 'suggestion_topic_id'], 'fb_sugg_topic_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback_suggestion_topic');
    }
};
