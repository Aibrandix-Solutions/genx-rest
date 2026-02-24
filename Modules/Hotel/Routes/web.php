<?php

use Illuminate\Support\Facades\Route;
use Modules\Hotel\Http\Controllers\HotelController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your module. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::middleware(['auth'])->prefix('hotel')->name('hotel.')->group(function() {
    Route::get('/', \Modules\Hotel\Livewire\Dashboard\HotelDashboard::class)->name('dashboard');
    Route::get('/room-types', \Modules\Hotel\Livewire\RoomType\RoomTypeList::class)->name('room-types');
    Route::get('/pricing', \Modules\Hotel\Livewire\Pricing\RoomPricingManager::class)->name('pricing');
    Route::get('/rooms', \Modules\Hotel\Livewire\Room\RoomList::class)->name('rooms');
    Route::get('/guests', \Modules\Hotel\Livewire\Guest\GuestList::class)->name('guests');
    Route::get('/reservations', \Modules\Hotel\Livewire\Reservation\ReservationList::class)->name('reservations');
    Route::get('/housekeeping', \Modules\Hotel\Livewire\Housekeeping\HousekeepingList::class)->name('housekeeping');
    Route::get('/reports', \Modules\Hotel\Livewire\Reports\ReportsDashboard::class)->name('reports');
    Route::get('/billing', \Modules\Hotel\Livewire\Billing\BillingDashboard::class)->name('billing');
    Route::get('/reservations/{reservationNumber}/folio', \Modules\Hotel\Livewire\Folio\FolioManager::class)->name('folio');
    Route::get('/invoice/{reservationId}', \Modules\Hotel\Livewire\Folio\InvoiceV2::class)->name('invoice');
    Route::get('/settings', \Modules\Hotel\Livewire\Settings\HotelSettingsPage::class)->name('settings');
});
