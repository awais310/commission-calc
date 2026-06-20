<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormulaVariable extends Model
{
    protected $fillable = [
        'formula_id',
        'variable_name',
        'expression',
        'variable_type',
        'depends_on',
        'execution_order',
    ];

    protected $casts = [
        'depends_on' => 'array',
    ];

    public function formula(): BelongsTo
    {
        return $this->belongsTo(CommissionFormula::class, 'formula_id');
    }
}
