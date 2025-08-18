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
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('social_media_platform_id')->constrained()->onDelete('cascade');
            $table->string('platform_user_id');
            $table->string('username');
            $table->string('display_name')->nullable();
            $table->string('profile_picture')->nullable();
            $table->bigInteger('follower_count')->default(0);
            $table->bigInteger('following_count')->default(0);
            $table->text('access_token');
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->json('platform_data')->nullable(); // store additional platform-specific data
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['customer_id', 'social_media_platform_id', 'platform_user_id']);
            $table->index(['is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_accounts');
    }
};
