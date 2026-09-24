<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonthlyReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'puskesmas_id',
        'year',
        'month',
        'status',
        'finalized_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'year'         => 'integer',
            'month'        => 'integer',
            'finalized_at' => 'datetime',
        ];
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isFinal(): bool
    {
        return $this->status === 'final';
    }

    public function puskesmas(): BelongsTo
    {
        return $this->belongsTo(Puskesmas::class);
    }

    public function kunjunganRows(): HasMany
    {
        return $this->hasMany(KunjunganRow::class);
    }

    public function layananRows(): HasMany
    {
        return $this->hasMany(LayananRow::class);
    }

    public function ocrUploads(): HasMany
    {
        return $this->hasMany(OcrUpload::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
