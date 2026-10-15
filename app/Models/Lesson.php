<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Lesson extends Model
{
    use HasFactory;

    /**
     * Поля, доступные для массового заполнения.
     *
     * @var list<string>
     */
    protected $fillable = [
        'course_id',
        'title',
        'content',
        'order',
        'attachment_path',
    ];

    /**
     * Приведение типов атрибутов.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'order' => 'integer',
    ];

    /**
     * Дополнительные вычисляемые поля для JSON-ответа.
     *
     * @var list<string>
     */
    protected $appends = [
        'attachment_url',
    ];

    /**
     * Курс, к которому принадлежит данный урок.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Получить публичную ссылку на файл дополнительных материалов.
     */
    public function getAttachmentUrlAttribute(): ?string
    {
        if (! $this->attachment_path) {
            return null;
        }

        return Storage::disk('public')->url($this->attachment_path);
    }
}
