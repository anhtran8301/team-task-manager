<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Index existing and future task titles without changing task data. */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->fullText('title', 'tasks_title_fulltext');
        });
    }

    /** Remove only the search index, preserving tasks and their foreign keys. */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropFullText('tasks_title_fulltext');
        });
    }
};
