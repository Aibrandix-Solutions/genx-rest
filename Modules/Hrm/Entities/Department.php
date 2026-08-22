<?php

namespace Modules\Hrm\Entities;

use App\Traits\HasRestaurant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Hrm\Support\Workplace;

class Department extends Model
{
    use HasFactory;
    use HasRestaurant;

    protected $table = 'hrm_departments';

    protected $guarded = [];

    protected $attributes = [
        'workplace' => Workplace::RESTAURANT,
    ];

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'department_id');
    }

    public function getWorkplaceLabelAttribute(): string
    {
        return Workplace::label($this->workplace);
    }
}
