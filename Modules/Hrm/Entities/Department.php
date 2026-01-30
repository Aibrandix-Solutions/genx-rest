<?php

namespace Modules\Hrm\Entities;

use App\Traits\HasRestaurant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasFactory;
    use HasRestaurant;

    protected $table = 'hrm_departments';

    protected $guarded = [];

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'department_id');
    }
}
