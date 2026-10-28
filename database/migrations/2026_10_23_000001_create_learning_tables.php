<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', fn (Blueprint $t) => [$t->id(), $t->string('name')->unique(), $t->text('description')->nullable(), $t->timestamps()]);
        Schema::create('courses', function (Blueprint $t) {
            $t->id();
            $t->foreignId('category_id')->constrained()->cascadeOnDelete();
            $t->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $t->string('title');
            $t->text('description');
            $t->decimal('price', 10, 2)->default(0);
            $t->string('status')->default('draft');
            $t->timestamp('published_at')->nullable();
            $t->timestamps();
            $t->index(['status', 'category_id']);
        });
        Schema::create('lessons', function (Blueprint $t) {
            $t->id();
            $t->foreignId('course_id')->constrained()->cascadeOnDelete();
            $t->string('title');
            $t->longText('content');
            $t->unsignedInteger('position');
            $t->string('file_path')->nullable();
            $t->timestamps();
            $t->unique(['course_id', 'position']);
        });
        Schema::create('purchases', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('course_id')->constrained()->cascadeOnDelete();
            $t->decimal('amount', 10, 2);
            $t->string('status')->default('pending');
            $t->string('payment_id')->nullable()->unique();
            $t->string('payment_url')->nullable();
            $t->timestamps();
            $t->unique(['user_id', 'course_id']);
        });
        Schema::create('lesson_progress', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $t->timestamp('completed_at')->nullable();
            $t->timestamps();
            $t->unique(['user_id', 'lesson_id']);
        });
        Schema::create('certificates', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('course_id')->constrained()->cascadeOnDelete();
            $t->string('file_path');
            $t->timestamps();
            $t->unique(['user_id', 'course_id']);
        });
    }

    public function down(): void
    {
        foreach (['certificates', 'lesson_progress', 'purchases', 'lessons', 'courses', 'categories'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
