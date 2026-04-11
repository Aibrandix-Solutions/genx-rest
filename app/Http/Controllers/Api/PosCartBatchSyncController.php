<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PosBatchSyncService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PosCartBatchSyncController extends Controller
{
    public function sync(Request $request, PosBatchSyncService $batchSyncService)
    {
        abort_if(!in_array('Order', restaurant_modules()) || !user_can('Create Order'), 403);

        $validated = $request->validate([
            'state' => ['required', 'array'],
            'operations' => ['required', 'array'],
            'operations.*' => ['required', 'array'],
            'operations.*.action' => ['required', 'string', Rule::in(['add', 'update', 'remove'])],
            'operations.*.qty' => ['nullable', 'integer', 'min:1', 'required_if:operations.*.action,update'],
        ]);

        $result = $batchSyncService->apply($validated['state'], $validated['operations']);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }
}
