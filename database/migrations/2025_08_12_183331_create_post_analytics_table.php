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
        Schema::create('post_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('published_post_id')->constrained()->onDelete('cascade');
            $table->string('platform');
            $table->string('platform_post_id');
            $table->bigInteger('views')->default(0);
            $table->bigInteger('likes')->default(0);
            $table->bigInteger('shares')->default(0);
            $table->bigInteger('comments')->default(0);
            $table->bigInteger('saves')->default(0);
            $table->decimal('engagement_rate', 5, 2)->default(0);
            $table->json('additional_metrics')->nullable();
            $table->timestamp('last_updated_at');
            $table->timestamps();

            $table->unique(['published_post_id', 'platform']);
            $table->index(['platform', 'platform_post_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_analytics');
    }
};
