<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('user_table_preferences')) {
            return;
        }

        Schema::create('user_table_preferences', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id');
            $table->string('table_key');
            $table->json('toggled_columns')->nullable();
            $table->json('saved_filters')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'table_key']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_table_preferences');
    }
};
