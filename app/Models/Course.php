<?php

namespace App\Models;

use App\Enums\CourseStatus;
use Database\Factories\CourseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Course extends Model
{
    /** @use HasFactory<CourseFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'price',
        'category_id',
        'author_id',
        'status',
        'published_at',
    ];

    /**
     * Преобразования атрибутов модели.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'status' => CourseStatus::class,
            'published_at' => 'datetime',
        ];
    }

    /**
     * Автоматическая генерация slug при создании курса.
     */
    protected static function booted(): void
    {
        static::creating(function (Course $course) {
            if (empty($course->slug)) {
                $baseSlug = Str::slug($course->title);
                $course->slug = $baseSlug.'-'.Str::lower(Str::random(6));
            }
        });
    }

    /**
     * Получить категорию курса.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Получить автора (преподавателя) курса.
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Получить уроки данного курса, отсортированные по порядку.
     */
    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('order');
    }

    /**
     * Проверить, опубликован ли курс.
     */
    public function isPublished(): bool
    {
        return $this->status === CourseStatus::Published;
    }

    /**
     * Проверить, является ли курс черновиком.
     */
    public function isDraft(): bool
    {
        return $this->status === CourseStatus::Draft;
    }

    /**
     * Область видимости: только опубликованные курсы.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', CourseStatus::Published->value);
    }

    /**
     * Область видимости: только черновики.
     */
    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', CourseStatus::Draft->value);
    }
}
