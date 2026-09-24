<?php

namespace App\Models;

use App\Support\LansiaFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KunjunganRow extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function __construct(array $attributes = [])
    {
        // Tetapkan fillable secara dinamis dari LansiaFields (Aturan 04_AGENT_RULES.md)
        $this->fillable = array_merge(
            ['monthly_report_id', 'kelurahan_id'],
            LansiaFields::allKunjunganRowColumns()
        );

        parent::__construct($attributes);
    }

    public function monthlyReport(): BelongsTo
    {
        return $this->belongsTo(MonthlyReport::class);
    }

    public function kelurahan(): BelongsTo
    {
        return $this->belongsTo(Kelurahan::class);
    }
}
