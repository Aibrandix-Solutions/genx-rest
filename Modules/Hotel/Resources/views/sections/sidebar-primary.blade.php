{{-- Hotel top-level sidebar items (used when business_mode = hotel_primary or equal) --}}
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

@endphp

@if(in_array('Hotel', restaurant_modules()) && $canSeeHotelMenu)

    @if(user_can('view_hotel_dashboard'))
        @livewire('sidebar-menu-item', [
            'name' => 'Dashboard',
            'icon' => 'dashboard',
            'link' => route('hotel.dashboard'),
            'active' => request()->routeIs('hotel.dashboard'),
            'customIcon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 transition duration-75 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z"/></svg>',
        ])
    @endif

    @if(user_can('view_hotel_reservations'))
        @livewire('sidebar-menu-item', [
            'name' => 'Reservations',
            'icon' => 'reservations',
            'link' => route('hotel.reservations'),
            'active' => request()->routeIs('hotel.reservations'),
            'customIcon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 transition duration-75 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>',
        ])
    @endif

    @if(user_can('view_hotel_guests'))
        @livewire('sidebar-menu-item', [
            'name' => 'Guests',
            'icon' => 'customers',
            'link' => route('hotel.guests'),
            'active' => request()->routeIs('hotel.guests'),
            'customIcon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 transition duration-75 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>',
        ])
    @endif

    @php
        $canSeeRoomSetup = user_can('view_hotel_room_types') || user_can('view_hotel_rooms');
    @endphp
    @if($canSeeRoomSetup)
        <x-sidebar-dropdown-menu name='Rooms' icon='table' customIcon='<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 transition duration-75 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 21v-4.875c0-.621.504-1.125 1.125-1.125h5.25c.621 0 1.125.504 1.125 1.125V21m0 0h4.5V3.545M12.75 21h7.5M10.5 21V11.625a2.25 2.25 0 00-2.25-2.25H3.75a2.25 2.25 0 00-2.25 2.25V21M10.5 21h-7.5M3.75 7.5h3M3.75 10.5h3m4.5-4.5h3m0 3h3M3 3h18"/></svg>' :active='request()->routeIs(["hotel.rooms", "hotel.room-types", "hotel.pricing"])'>
            @if(user_can('view_hotel_rooms'))
                @livewire('sidebar-dropdown-menu', ['name' => 'All Rooms', 'link' => route('hotel.rooms'), 'active' => request()->routeIs('hotel.rooms')])
            @endif
            @if(user_can('view_hotel_room_types'))
                @livewire('sidebar-dropdown-menu', ['name' => 'Room Types', 'link' => route('hotel.room-types'), 'active' => request()->routeIs('hotel.room-types')])
            @endif
            @if(user_can('manage_room_pricing') && $dynamicPricingEnabledPrimary)
                @livewire('sidebar-dropdown-menu', ['name' => 'Pricing', 'link' => route('hotel.pricing'), 'active' => request()->routeIs('hotel.pricing')])
            @endif
        </x-sidebar-dropdown-menu>
    @endif

        @if(user_can('view_hotel_housekeeping') && $housekeepingEnabledPrimary)
            @livewire('sidebar-menu-item', [
                'name' => 'Housekeeping',
                'icon' => 'housekeeping',
                'link' => route('hotel.housekeeping'),
                'active' => request()->routeIs('hotel.housekeeping'),
                'customIcon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 transition duration-75 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z"/></svg>',
            ])
        @endif

        @if(user_can('view_hotel_reports'))
            @livewire('sidebar-menu-item', [
                'name' => 'Reports',
                'icon' => 'reports',
                'link' => route('hotel.reports'),
                'active' => request()->routeIs('hotel.reports'),
                'customIcon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 transition duration-75 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/></svg>',
            ])
        @endif

        @if(user_can('view_hotel_expenses'))
            @livewire('sidebar-menu-item', [
                'name' => 'Expenses',
                'icon' => 'expenses',
                'link' => route('hotel.expenses'),
                'active' => request()->routeIs('hotel.expenses'),
                'customIcon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 transition duration-75 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z"/></svg>',
            ])
        @endif

        @if(user_can('view_unified_finance_report'))
            @livewire('sidebar-menu-item', [
                'name' => 'Finance Report',
                'icon' => 'finance',
                'link' => route('hotel.finance'),
                'active' => request()->routeIs('hotel.finance'),
                'customIcon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 transition duration-75 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 14.25v2.25m3-4.5v4.5m3-6.75v6.75m3-9v9M6 20.25h12A2.25 2.25 0 0 0 20.25 18V6A2.25 2.25 0 0 0 18 3.75H6A2.25 2.25 0 0 0 3.75 6v12A2.25 2.25 0 0 0 6 20.25Z"/></svg>',
            ])
        @endif

        @if(user_can('view_property_pnl'))
            @livewire('sidebar-menu-item', [
                'name' => 'P&L Dashboard',
                'icon' => 'pnl',
                'link' => route('hotel.profit-loss'),
                'active' => request()->routeIs('hotel.profit-loss'),
                'customIcon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 transition duration-75 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6 9 12.75l4.286-4.286a11.948 11.948 0 0 1 4.306 6.43l.776 2.898m0 0 3.182-5.511m-3.182 5.511-5.511-3.182"/></svg>',
            ])
        @endif

        @if(user_can('view_hotel_billing'))
            @livewire('sidebar-menu-item', [
                'name' => 'Billing',
                'icon' => 'billing',
                'link' => route('hotel.billing'),
                'active' => request()->routeIs('hotel.billing'),
                'customIcon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 transition duration-75 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z"/></svg>',
            ])
            @livewire('sidebar-menu-item', [
                'name' => __('hotel::modules.menu.restaurantDues'),
                'icon' => 'billing',
                'link' => route('hotel.restaurant-dues'),
                'active' => request()->routeIs('hotel.restaurant-dues'),
                'customIcon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 transition duration-75 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white"><path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5V6a2.25 2.25 0 0 0-2.25-2.25h-13.5A2.25 2.25 0 0 0 3 6v12a2.25 2.25 0 0 0 2.25 2.25h13.5A2.25 2.25 0 0 0 21 18v-1.5m-18 0h18m-18-9h18m-3 4.5h.008v.008H18V12Zm-3 0h.008v.008H15V12Z"/></svg>',
            ])
        @endif

    @if(user_can('manage_hotel_settings'))
        @livewire('sidebar-menu-item', [
            'name' => 'Hotel Settings',
            'icon' => 'settings',
            'link' => route('hotel.settings'),
            'active' => request()->routeIs('hotel.settings'),
        ])
    @endif

@endif
