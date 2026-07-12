<?php

namespace Modules\Hotel\Entities;

use App\Traits\HasBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HotelExpenseDepartment extends Model
{
    use HasFactory, HasBranch;

    protected $table = 'hotel_expense_departments';

    protected $fillable = [
        'branch_id',
        'restaurant_id',
        'name',
        'slug',
        'description',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];
}
