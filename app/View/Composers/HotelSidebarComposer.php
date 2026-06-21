<?php

namespace App\View\Composers;

use Illuminate\View\View;
use Modules\Hotel\Entities\HotelSetting;

class HotelSidebarComposer
{
    public function compose(View $view): void
    {
        $restaurant = restaurant();
        $settings = $restaurant
            ? HotelSetting::where('restaurant_id', $restaurant->id)->first()
            : null;

        $view->with([
            'housekeepingEnabledPrimary' => $settings ? (bool) $settings->enable_housekeeping_module : true,
            'roomServiceEnabledPrimary' => $settings ? (bool) $settings->enable_room_service : true,
            'dynamicPricingEnabledPrimary' => $settings ? (bool) $settings->enable_dynamic_pricing : false,
        ]);
    }
}
