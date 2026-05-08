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
        Schema::create('whispers', function (Blueprint $table) {
            $table->id();

            // ささやき本文
            $table->text('content');

            // 親ささやきID（リツイート用）
            $table->foreignId('whisper_id')
                ->nullable()
                ->constrained('whispers')
                ->nullOnDelete();

            // 投稿ユーザー
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whispers');
    }
};
