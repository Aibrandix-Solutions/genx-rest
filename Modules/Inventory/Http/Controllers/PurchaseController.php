<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    public function index()
    {
        abort_if(!in_array('Inventory', restaurant_modules()), 403);
        return view('inventory::purchase.index');
    }

    public function returns()
    {
        abort_if(!in_array('Inventory', restaurant_modules()), 403);
        return view('inventory::purchase.returns');
    }
}

