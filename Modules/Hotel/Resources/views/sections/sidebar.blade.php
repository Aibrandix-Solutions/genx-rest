@php
  $canSeeHotelMenu = user_can('view_hotel_dashboard')
    || user_can('view_hotel_room_types')
    || user_can('view_hotel_rooms')
    || user_can('view_hotel_guests')
    || user_can('view_hotel_reservations')
    || user_can('view_hotel_billing')
    || user_can('view_hotel_housekeeping')
    || user_can('view_hotel_reports')
    || user_can('manage_room_pricing')
    || user_can('view_hotel_expenses')
    || user_can('view_unified_finance_report')
    || user_can('view_property_pnl')
    || user_can('manage_hotel_settings');

  // Read feature flags from hotel settings (cached to avoid N+1)
  $hotelSettings = \Modules\Hotel\Entities\HotelSetting::where('restaurant_id', restaurant()->id)->first();
  $housekeepingEnabled    = $hotelSettings ? (bool) $hotelSettings->enable_housekeeping_module : true;
  $dynamicPricingEnabled  = $hotelSettings ? (bool) $hotelSettings->enable_dynamic_pricing    : false;
  $roomServiceEnabled     = $hotelSettings ? (bool) $hotelSettings->enable_room_service        : true;
@endphp

@if(in_array('Hotel', restaurant_modules()) && $canSeeHotelMenu)
<x-sidebar-dropdown-menu :name='"Hotel Management"' isAddon="true" icon='hotel' customIcon='<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="w-6 h-6 transition duration-75 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white" viewBox="0 0 16 16">
  <path d="M3 13.5a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5zm1.5-10V1a1 1 0 0 1 1-1h5a1 1 0 0 1 1 1v2.5h1a1 1 0 0 1 1 1V14a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V4.5a1 1 0 0 1 1-1h1.5zM5 2v2.5h6V2H5zm-.5 3.5a.5.5 0 0 0-.5.5v8h8V6a.5.5 0 0 0-.5-.5h-7zM5 7.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 0 1h-1a.5.5 0 0 1-.5-.5zm0 2a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 0 1h-1a.5.5 0 0 1-.5-.5zm0 2a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 0 1h-1a.5.5 0 0 1-.5-.5zm4-4a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 0 1h-1a.5.5 0 0 1-.5-.5zm0 2a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 0 1h-1a.5.5 0 0 1-.5-.5zm0 2a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 0 1h-1a.5.5 0 0 1-.5-.5z"/>
</svg>' :active='request()->routeIs(["hotel.*"])'>

  @if(user_can('view_hotel_dashboard'))
    @livewire('sidebar-dropdown-menu', ['name' => 'Dashboard', 'link' => route('hotel.dashboard'), 'active' => request()->routeIs('hotel.dashboard')])
  @endif
    
  @if(user_can('view_hotel_room_types'))
    @livewire('sidebar-dropdown-menu', ['name' => 'Room Types', 'link' => route('hotel.room-types'), 'active' => request()->routeIs('hotel.room-types')])
  @endif

  @if(user_can('manage_room_pricing') && $dynamicPricingEnabled)
    @livewire('sidebar-dropdown-menu', ['name' => 'Pricing', 'link' => route('hotel.pricing'), 'active' => request()->routeIs('hotel.pricing')])
  @endif
    
  @if(user_can('view_hotel_rooms'))
    @livewire('sidebar-dropdown-menu', ['name' => 'Rooms', 'link' => route('hotel.rooms'), 'active' => request()->routeIs('hotel.rooms')])
  @endif
    
  @if(user_can('view_hotel_guests'))
    @livewire('sidebar-dropdown-menu', ['name' => 'Guests', 'link' => route('hotel.guests'), 'active' => request()->routeIs('hotel.guests')])
  @endif
    
  @if(user_can('view_hotel_reservations'))
    @livewire('sidebar-dropdown-menu', ['name' => 'Reservations', 'link' => route('hotel.reservations'), 'active' => request()->routeIs('hotel.reservations')])
  @endif

  @if(user_can('view_hotel_billing'))
    @livewire('sidebar-dropdown-menu', ['name' => 'Billing', 'link' => route('hotel.billing'), 'active' => request()->routeIs('hotel.billing')])
  @endif

  @if(user_can('view_hotel_housekeeping') && $housekeepingEnabled)
    @livewire('sidebar-dropdown-menu', ['name' => 'Housekeeping', 'link' => route('hotel.housekeeping'), 'active' => request()->routeIs('hotel.housekeeping')])
  @endif

  @if(user_can('view_hotel_reports'))
    @livewire('sidebar-dropdown-menu', ['name' => 'Reports', 'link' => route('hotel.reports'), 'active' => request()->routeIs('hotel.reports')])
  @endif

  @if(user_can('view_hotel_expenses'))
    @livewire('sidebar-dropdown-menu', ['name' => 'Expenses', 'link' => route('hotel.expenses'), 'active' => request()->routeIs('hotel.expenses')])
  @endif

  @if(user_can('view_unified_finance_report'))
    @livewire('sidebar-dropdown-menu', ['name' => 'Finance Report', 'link' => route('hotel.finance'), 'active' => request()->routeIs('hotel.finance')])
  @endif

  @if(user_can('view_property_pnl'))
    @livewire('sidebar-dropdown-menu', ['name' => 'P&L Dashboard', 'link' => route('hotel.profit-loss'), 'active' => request()->routeIs('hotel.profit-loss')])
  @endif

  @if(user_can('manage_hotel_settings'))
    @livewire('sidebar-dropdown-menu', ['name' => 'Settings', 'link' => route('hotel.settings'), 'active' => request()->routeIs('hotel.settings')])
  @endif

</x-sidebar-dropdown-menu>
@endif
