<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Apply this schema change without deleting existing business records. */
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', ['todo', 'in_progress', 'done'])->default('todo');
            $table->foreignId('assigned_to')->constrained('users')->restrictOnDelete();
            $table->date('due_date')->nullable();
            $table->timestamps();
            $table->index(['assigned_to', 'id']);
            $table->index(['status', 'id']);
            $table->index(['assigned_to', 'status', 'id']);
        });
    }

    /** Reverse the schema change. */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
