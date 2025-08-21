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
        Schema::create('post_insights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('published_post_id')->constrained('social_posts')->onDelete('cascade');
            $table->bigInteger('views')->default(0);
            $table->bigInteger('likes')->default(0);
            $table->bigInteger('shares')->default(0);
            $table->bigInteger('comments')->default(0);
            $table->bigInteger('saves')->default(0);
            $table->decimal('engagement_rate', 5, 2)->default(0);
            $table->json('additional_metrics')->nullable();
            $table->timestamp('last_updated_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_insights');
    }
};
