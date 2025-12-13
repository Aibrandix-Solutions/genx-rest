<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;

class PurchaseReturnController extends Controller
{
    /**
     * Display the purchase returns page.
     */
    public function index()
    {
        abort_if(!in_array('Inventory', restaurant_modules()), 403);
        abort_if(!user_can('Show Purchase Return'), 403);
        
        return view('inventory::purchase-returns.index');
    }
}


