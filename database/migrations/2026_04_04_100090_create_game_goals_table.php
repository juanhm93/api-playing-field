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
        Schema::create('game_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained('games')->onDelete('cascade');
            $table->foreignId('team_id')->constrained('teams')->onDelete('cascade');
            $table->foreignId('scorer_player_id')->constrained('players')->onDelete('cascade');
            $table->foreignId('assist_player_id')->nullable()->constrained('players')->nullOnDelete();
            $table->unsignedSmallInteger('minute');
            $table->boolean('is_penalty')->default(false);
            $table->boolean('is_own_goal')->default(false);
            $table->timestamps();

            $table->index(['game_id', 'team_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_goals');
    }
};
