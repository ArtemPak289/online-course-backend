<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'course_id', 'amount', 'status', 'payment_id', 'payment_url'];

    protected $casts = ['amount' => 'decimal:2'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function isPaid()
    {
        return $this->status === 'paid';
    }
}
