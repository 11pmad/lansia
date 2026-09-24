<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OcrUpload extends Model
{
    use HasFactory;

    protected $fillable = [
        'monthly_report_id',
        'kelurahan_id',
        'section',
        'image_path',
        'status',
        'result_json',
        'confidence_json',
        'error',
        'created_by',
        'applied_at',
    ];

    protected function casts(): array
    {
        return [
            'result_json'     => 'array',
            'confidence_json' => 'array',
            'applied_at'      => 'datetime',
        ];
    }

    public function monthlyReport(): BelongsTo
    {
        return $this->belongsTo(MonthlyReport::class);
    }

    public function kelurahan(): BelongsTo
    {
        return $this->belongsTo(Kelurahan::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
