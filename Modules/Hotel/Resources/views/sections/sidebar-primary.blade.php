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
    || user_can('manage_hotel_settings');
@endphp

@if(in_array('Hotel', restaurant_modules()) && $canSeeHotelMenu)

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
        <x-sidebar-dropdown-menu name='Rooms' icon='table' customIcon='<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 transition duration-75 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 21v-4.875c0-.621.504-1.125 1.125-1.125h5.25c.621 0 1.125.504 1.125 1.125V21m0 0h4.5V3.545M12.75 21h7.5M10.5 21V11.625a2.25 2.25 0 00-2.25-2.25H3.75a2.25 2.25 0 00-2.25 2.25V21M10.5 21h-7.5M3.75 7.5h3M3.75 10.5h3m4.5-4.5h3m0 3h3M3 3h18"/></svg>' :active='request()->routeIs(["hotel.rooms", "hotel.room-types"])'>
            @if(user_can('view_hotel_rooms'))
                @livewire('sidebar-dropdown-menu', ['name' => 'All Rooms', 'link' => route('hotel.rooms'), 'active' => request()->routeIs('hotel.rooms')])
            @endif
            @if(user_can('view_hotel_room_types'))
                @livewire('sidebar-dropdown-menu', ['name' => 'Room Types', 'link' => route('hotel.room-types'), 'active' => request()->routeIs('hotel.room-types')])
            @endif
        </x-sidebar-dropdown-menu>
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
