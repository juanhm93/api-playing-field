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
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('lastname')->nullable();
            $table->string('nickname')->nullable();
            $table->string('slug')->unique();
            $table->string('code')->nullable();
            $table->string('photo')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('nationality')->nullable();
            $table->string('height')->nullable();
            $table->string('weight')->nullable();
            $table->string('foot')->nullable();
            $table->foreignId('position_id')->constrained('positions')->onDelete('cascade');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('player_team_season', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained('players')->onDelete('cascade');
            $table->foreignId('team_season_id')->constrained('team_season')->onDelete('cascade');
            $table->integer('number')->default(0);
            $table->boolean('is_started')->default(false);
            $table->timestamps();

            $table->unique(['player_id', 'team_season_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('player_team_season');
        Schema::dropIfExists('players');
    }
};
