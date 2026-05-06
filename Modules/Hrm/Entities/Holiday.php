<?php

namespace Modules\Hrm\Entities;

use App\Models\Branch;
use App\Traits\HasRestaurant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Holiday extends Model
{
    use HasFactory;
    use HasRestaurant;

    protected $table = 'hrm_holidays';

    protected $guarded = [];

    protected $casts = [
        'date' => 'date',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
}
