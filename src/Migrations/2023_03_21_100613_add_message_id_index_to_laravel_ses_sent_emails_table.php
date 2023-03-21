<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('laravel_ses_sent_emails', function (Blueprint $table) {
            $table->index('message_id');
        });
    }

    public function down(): void
    {
        Schema::table('laravel_ses_sent_emails', function (Blueprint $table) {
            $table->dropIndex('message_id');
        });
    }
};