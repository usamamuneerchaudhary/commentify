<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->string('guest_name')->nullable()->after('user_id');
            $table->string('guest_email')->nullable()->after('guest_name');
            $table->string('ip', 45)->nullable()->after('guest_email');
            $table->text('user_agent')->nullable()->after('ip');
            $table->string('import_source')->nullable()->after('user_agent');
            $table->string('import_id')->nullable()->after('import_source');

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['import_source', 'import_id']);
        });
    }

    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropIndex(['import_source', 'import_id']);
            $table->dropColumn(['guest_name', 'guest_email', 'ip', 'user_agent', 'import_source', 'import_id']);
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
};
