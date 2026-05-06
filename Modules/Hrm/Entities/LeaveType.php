<?php

namespace Modules\Hrm\Entities;

use App\Traits\HasRestaurant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    use HasFactory;
    use HasRestaurant;

    protected $table = 'hrm_leave_types';

    protected $guarded = [];

    protected $casts = [
        'max_per_year' => 'integer',
        'is_paid' => 'boolean',
        'is_active' => 'boolean',
    ];
}
