<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeliveryExecutive;
use App\Models\DeliveryPlatform;
use App\Models\ItemCategory;
use App\Models\OrderType;
use App\Models\Tax;
use App\Models\User;
use App\Services\PosBootstrapService;
use App\Scopes\BranchScope;
use Illuminate\Support\Facades\Cache;

class PosBootstrapController extends Controller
{
    /**
     * Get cached POS bootstrap data (menus, categories, taxes, etc.)
     * 
     * This endpoint returns all read-only data needed to initialize a POS session,
     * cached for performance. Cache is invalidated when menus or settings change.
     */
    public function bootstrap(PosBootstrapService $bootstrapService)
    {
        // Verify user has POS access
        abort_if(!in_array('Order', restaurant_modules()) || !user_can('Create Order'), 403);
        $resolved = $bootstrapService->resolve();
        $data = $resolved['data'];

        return response()->json([
            'success' => true,
            'data' => $data,
            'cached' => $resolved['cached'],
        ]);
    }

    /**
     * Clear POS bootstrap cache (called when menus/settings change)
     */
    public function clearCache(PosBootstrapService $bootstrapService)
    {
        abort_if(!auth()->check() || !user_can('Manage Settings'), 403);

        $currentBranch = branch();
        $bootstrapService->clearCache(restaurant()->id, is_object($currentBranch) ? $currentBranch->id : null);

        return response()->json([
            'success' => true,
            'message' => 'POS bootstrap cache cleared',
        ]);
    }
}
