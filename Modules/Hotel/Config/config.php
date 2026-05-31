<?php

$addOnOf = 'tabletrack';

return [
    // Module installation metadata
    'name' => 'Hotel',
    'verification_required' => false,
    'envato_item_id' => 99999997,              // Placeholder
    'parent_envato_id' => 55116396,            // TableTrack main app Envato ID
    'parent_min_version' => '1.2.40',          // Requires TableTrack >= 1.2.40
    'script_name' => $addOnOf . '-hotel-module',
    'parent_product_name' => $addOnOf,
    'setting' => \Modules\Hotel\Entities\HotelSetting::class,
];
