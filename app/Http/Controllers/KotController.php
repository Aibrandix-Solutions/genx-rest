<?php

namespace App\Http\Controllers;

use App\Models\Kot;
use App\Helper\Files;
use Illuminate\Support\Facades\Log;
use App\Models\KotPlace;
use App\Models\Printer;

class KotController extends Controller
{
    protected $connector;
    protected $printer;

    public function index()
    {
        abort_if(!in_array('KOT', restaurant_modules()), 303);
        abort_if((!user_can('Manage KOT')), 303);
        return view('kot.index');
    }

    public function printKot($id, $kotPlaceid = null, $width = 56, $thermal = false)
    {
        // Eager-load everything the print blade touches so the tab opens without N+1 queries.
        $kot = Kot::with([
            'items.menuItem',
            'items.menuItemVariation',
            'items.modifierOptions',
            'order.waiter',
            'order.table',
            'branch.restaurant',
        ])->find($id);
        $kotPlace = $kotPlaceid ? KotPlace::find($kotPlaceid) : null;

        return view('pos.printKot', compact('kot', 'kotPlaceid', 'width', 'thermal', 'kotPlace'));
    }
}
