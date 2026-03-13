<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_institution_permissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'institution_id'], 'user_institution_permissions_unique');
            $table->index('institution_id', 'user_institution_permissions_institution_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_institution_permissions');
    }
};

