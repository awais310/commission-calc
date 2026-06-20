<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommissionFormula extends Model
{
    protected $fillable = [
        'name',
        'description',
        'version',
        'expression',
        'variables_used',
        'is_active',
        'status',
        'created_by',
        'activated_at',
        'archived_at',
    ];

    protected $casts = [
        'variables_used' => 'array',
        'is_active'      => 'boolean',
        'activated_at'   => 'datetime',
        'archived_at'    => 'datetime',
    ];

    public function variables(): HasMany
    {
        return $this->hasMany(FormulaVariable::class, 'formula_id')->orderBy('execution_order');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->whereIn('status', ['draft', 'validated']);
    }
}
