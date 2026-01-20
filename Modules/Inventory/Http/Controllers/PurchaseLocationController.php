<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;

class PurchaseLocationController extends Controller
{
    public function index()
    {
        abort_if(!in_array('Inventory', restaurant_modules()), 403);
        abort_if(!user_can('Manage Locations'), 403);

        return view('inventory::purchase-locations.index');
    }
}
