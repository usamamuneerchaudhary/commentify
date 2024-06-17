<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('comments')->onDelete('cascade');
            $table->text('body');
            $table->morphs('commentable');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::table('comments', function (Blueprint $table) {
            if (config('commentify.user_uuid')) {
                $table->foreignUuid('user_id')->constrained()->onDelete('cascade')->change();
            }

            if (config('commentify.parent_uuid')) {
                $table->foreignUuid('parent_id')->nullable()->constrained('comments')->onDelete('cascade')->change();
            }

            $table->index('parent_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
