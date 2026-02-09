<?php

namespace Modules\Hotel\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class HotelController extends Controller
{
    /**
     * Display hotel dashboard
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return redirect()->route('hotel.dashboard');
    }
}
