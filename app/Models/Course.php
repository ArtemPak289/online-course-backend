<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    protected $fillable = ['category_id', 'author_id', 'title', 'description', 'price', 'status', 'published_at'];

    protected $casts = ['price' => 'decimal:2', 'published_at' => 'datetime'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function lessons()
    {
        return $this->hasMany(Lesson::class)->orderBy('position');
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    public function isPublished()
    {
        return $this->status === 'published';
    }
}
