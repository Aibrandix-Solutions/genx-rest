<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderExtra extends BaseModel
{
	protected $guarded = ['id'];

	protected $casts = [
		'amount' => 'float',
	];

	public function order(): BelongsTo
	{
		return $this->belongsTo(Order::class);
	}
}
