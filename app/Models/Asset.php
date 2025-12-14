<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'symbol',
        'amount',
        'locked_amount',
    ];

    protected $casts = [
        'amount' => 'decimal:8',
        'locked_amount' => 'decimal:8',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getAvailableAmount(): string
    {
        return $this->amount;
    }

    public function getTotalAmount(): string
    {
        return bcadd($this->amount, $this->locked_amount, 8);
    }
}
