@extends('layouts.landing')

@section('content')

{{-- Hero Section with Sri Lankan-inspired gradient --}}
<section class="relative overflow-hidden">
    {{-- Decorative background --}}
    <div class="absolute inset-0 bg-gradient-to-br from-amber-50 via-orange-50 to-red-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900"></div>
    <div class="absolute top-0 right-0 w-96 h-96 bg-gradient-to-bl from-amber-200/30 to-transparent rounded-full blur-3xl"></div>
    <div class="absolute bottom-0 left-0 w-80 h-80 bg-gradient-to-tr from-orange-200/30 to-transparent rounded-full blur-3xl"></div>
    
    {{-- Subtle pattern overlay --}}
    <div class="absolute inset-0 opacity-5" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23000000\' fill-opacity=\'0.4\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>

    <div class="relative py-16 px-4 mx-auto max-w-screen-xl text-center lg:py-24 lg:px-12">
        {{-- Badge --}}
        <div class="inline-flex items-center gap-2 px-4 py-2 mb-6 text-sm font-medium text-amber-800 bg-amber-100 rounded-full dark:bg-amber-900/30 dark:text-amber-300">
            <span class="relative flex h-2 w-2">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-500 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-amber-600"></span>
            </span>
            🇱🇰 Made for Sri Lankan Restaurants
        </div>

        <h1 class="mb-6 text-4xl font-black tracking-tight leading-none text-gray-900 md:text-5xl lg:text-6xl dark:text-white">
            <span class="block">ඔබේ ආපනශාලාව</span>
            <span class="block mt-2 bg-gradient-to-r from-amber-600 via-orange-500 to-red-500 bg-clip-text text-transparent">
                @lang('landing.heroTitle')
            </span>
        </h1>
        
        <p class="mb-10 text-lg font-normal text-gray-600 lg:text-xl sm:px-16 xl:px-48 dark:text-gray-300">
            @lang('landing.heroSubTitle')
        </p>
        
        <div class="flex flex-col mb-12 lg:mb-20 space-y-4 sm:flex-row sm:justify-center sm:space-y-0 sm:space-x-4 rtl:space-x-reverse">
            <a href="{{ route('restaurant_signup') }}"
                class="group relative inline-flex items-center justify-center py-4 px-8 text-base font-bold text-white rounded-xl bg-gradient-to-r from-amber-500 via-orange-500 to-red-500 hover:from-amber-600 hover:via-orange-600 hover:to-red-600 shadow-lg shadow-orange-500/30 hover:shadow-orange-500/50 transition-all duration-300 transform hover:-translate-y-0.5">
                @if($trialPackage)
                    @lang('landing.startTrial', ['days' => $trialPackage->trial_days])
                @else
                    @lang('landing.getStartedFree')
                @endif
                <svg class="ml-2 rtl:ml-0 rtl:mr-2 -mr-1 w-5 h-5 transition-transform group-hover:translate-x-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>
            </a>
            <a href="#icon-features" class="inline-flex items-center justify-center py-4 px-8 text-base font-semibold text-gray-700 bg-white rounded-xl border-2 border-gray-200 hover:border-amber-300 hover:bg-amber-50 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:border-amber-500 transition-all duration-300">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                See Features
            </a>
        </div>

        {{-- Dashboard Preview --}}
        <div class="relative max-w-5xl mx-auto">
            <div class="absolute -inset-4 bg-gradient-to-r from-amber-400 via-orange-400 to-red-400 rounded-2xl blur-2xl opacity-20"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden border border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-2 px-4 py-3 bg-gray-100 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                    <div class="w-3 h-3 rounded-full bg-red-400"></div>
                    <div class="w-3 h-3 rounded-full bg-amber-400"></div>
                    <div class="w-3 h-3 rounded-full bg-green-400"></div>
                </div>
                <img src="{{ asset('landing/dashboard.png') }}" class="w-full" alt="Restaurant POS Dashboard">
            </div>
            
            {{-- Floating Stats Cards --}}
            <div class="hidden lg:block absolute -left-8 top-1/4 bg-white dark:bg-gray-800 rounded-xl shadow-xl p-4 border border-gray-100 dark:border-gray-700 animate-bounce" style="animation-duration: 3s;">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                </svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Today's Sales</p>
                        <p class="font-bold text-gray-900 dark:text-white">Rs. 125,450</p>
                    </div>
                </div>
            </div>
            
            <div class="hidden lg:block absolute -right-8 top-1/3 bg-white dark:bg-gray-800 rounded-xl shadow-xl p-4 border border-gray-100 dark:border-gray-700 animate-bounce" style="animation-duration: 4s; animation-delay: 0.5s;">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-amber-100 dark:bg-amber-900/30 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Orders Today</p>
                        <p class="font-bold text-gray-900 dark:text-white">47 Orders</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Trusted By Section --}}
<section class="py-12 bg-white dark:bg-gray-900 border-y border-gray-100 dark:border-gray-800">
    <div class="max-w-screen-xl mx-auto px-4">
        <p class="text-center text-sm font-medium text-gray-500 dark:text-gray-400 mb-8">
            Trusted by restaurants across Sri Lanka - from Colombo to Kandy, Galle to Jaffna
        </p>
        <div class="flex flex-wrap justify-center items-center gap-8 lg:gap-16 opacity-60 dark:opacity-40">
            <div class="text-2xl font-bold text-gray-400">🍛 Rice & Curry</div>
            <div class="text-2xl font-bold text-gray-400">🦐 Seafood Hut</div>
            <div class="text-2xl font-bold text-gray-400">☕ Ceylon Café</div>
            <div class="text-2xl font-bold text-gray-400">🍜 Kottu King</div>
            <div class="text-2xl font-bold text-gray-400">🥘 Hoppers House</div>
        </div>
    </div>
</section>

{{-- Features Section --}}
<section class="py-20 bg-gradient-to-b from-white to-gray-50 dark:from-gray-900 dark:to-gray-800">
    <div class="max-w-[85rem] px-4 sm:px-6 lg:px-8 mx-auto">
        
        {{-- Section Header --}}
        <div class="text-center mb-16">
            <span class="inline-block px-3 py-1 text-sm font-semibold text-amber-600 bg-amber-100 rounded-full dark:bg-amber-900/30 dark:text-amber-400 mb-4">
                Features
            </span>
            <h2 class="text-3xl lg:text-5xl font-bold text-gray-900 dark:text-white mb-4">
                @lang('landing.featureSection1')
            </h2>
            <p class="text-lg text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
                Everything you need to run your restaurant efficiently, from orders to inventory
            </p>
        </div>

        {{-- Feature 1 --}}
        <div class="grid md:grid-cols-2 gap-12 lg:gap-20 items-center mb-20">
            <div class="relative">
                <div class="absolute -inset-4 bg-gradient-to-r from-amber-400 to-orange-400 rounded-2xl blur-2xl opacity-20"></div>
                <img class="relative rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700" 
                     src="{{ asset('landing/order-management.png') }}" alt="Order Management">
            </div>
            <div class="space-y-6">
                <div class="inline-flex items-center gap-2 px-3 py-1 bg-green-100 dark:bg-green-900/30 rounded-full">
                    <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                    <span class="text-sm font-medium text-green-700 dark:text-green-400">Order Management</span>
                </div>
                <h3 class="text-3xl lg:text-4xl font-bold text-gray-900 dark:text-white">
                    @lang('landing.featureTitle1')
                </h3>
                <p class="text-lg text-gray-600 dark:text-gray-400 leading-relaxed">
                    @lang('landing.featureDescription1')
                </p>
                <ul class="space-y-3">
                    <li class="flex items-center gap-3 text-gray-700 dark:text-gray-300">
                        <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        Dine-in, Takeaway & Delivery - all in one
                    </li>
                    <li class="flex items-center gap-3 text-gray-700 dark:text-gray-300">
                        <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        Real-time KOT (Kitchen Order Tickets)
                    </li>
                    <li class="flex items-center gap-3 text-gray-700 dark:text-gray-300">
                        <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        Track order status instantly
                    </li>
                </ul>
            </div>
        </div>

        {{-- Feature 2 --}}
        <div class="grid md:grid-cols-2 gap-12 lg:gap-20 items-center mb-20">
            <div class="space-y-6 md:order-1 order-2">
                <div class="inline-flex items-center gap-2 px-3 py-1 bg-blue-100 dark:bg-blue-900/30 rounded-full">
                    <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                    <span class="text-sm font-medium text-blue-700 dark:text-blue-400">Table Management</span>
    </div>
                <h3 class="text-3xl lg:text-4xl font-bold text-gray-900 dark:text-white">
                        @lang('landing.featureTitle2')
                </h3>
                <p class="text-lg text-gray-600 dark:text-gray-400 leading-relaxed">
                        @lang('landing.featureDescription2')
                    </p>
                <ul class="space-y-3">
                    <li class="flex items-center gap-3 text-gray-700 dark:text-gray-300">
                        <svg class="w-5 h-5 text-blue-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        Visual floor plan editor
                    </li>
                    <li class="flex items-center gap-3 text-gray-700 dark:text-gray-300">
                        <svg class="w-5 h-5 text-blue-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        Online reservations with WhatsApp notifications
                    </li>
                    <li class="flex items-center gap-3 text-gray-700 dark:text-gray-300">
                        <svg class="w-5 h-5 text-blue-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        Multiple areas & outdoor seating support
                    </li>
                </ul>
                </div>
            <div class="relative md:order-2 order-1">
                <div class="absolute -inset-4 bg-gradient-to-r from-blue-400 to-cyan-400 rounded-2xl blur-2xl opacity-20"></div>
                <img class="relative rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700" 
                     src="{{ asset('landing/table-reservation.png') }}" alt="Table Reservation">
            </div>
        </div>

        {{-- Feature 3 --}}
        <div class="grid md:grid-cols-2 gap-12 lg:gap-20 items-center">
            <div class="relative">
                <div class="absolute -inset-4 bg-gradient-to-r from-purple-400 to-pink-400 rounded-2xl blur-2xl opacity-20"></div>
                <img class="relative rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700" 
                     src="{{ asset('landing/menu-management.png') }}" alt="Menu Management">
    </div>
            <div class="space-y-6">
                <div class="inline-flex items-center gap-2 px-3 py-1 bg-purple-100 dark:bg-purple-900/30 rounded-full">
                    <span class="w-2 h-2 bg-purple-500 rounded-full"></span>
                    <span class="text-sm font-medium text-purple-700 dark:text-purple-400">Menu Management</span>
        </div>
                <h3 class="text-3xl lg:text-4xl font-bold text-gray-900 dark:text-white">
                        @lang('landing.featureTitle3')
                </h3>
                <p class="text-lg text-gray-600 dark:text-gray-400 leading-relaxed">
                        @lang('landing.featureDescription3')
                    </p>
                <ul class="space-y-3">
                    <li class="flex items-center gap-3 text-gray-700 dark:text-gray-300">
                        <svg class="w-5 h-5 text-purple-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        Categories for Rice & Curry, Kottu, Hoppers & more
                    </li>
                    <li class="flex items-center gap-3 text-gray-700 dark:text-gray-300">
                        <svg class="w-5 h-5 text-purple-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        Add-ons & variations (Egg, Cheese, Extra Spicy)
                    </li>
                    <li class="flex items-center gap-3 text-gray-700 dark:text-gray-300">
                        <svg class="w-5 h-5 text-purple-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        Beautiful digital menu with QR code
                    </li>
                </ul>
            </div>
        </div>
    </div>
</section>

{{-- Icon Features Grid --}}
<section class="py-20 bg-gray-900 dark:bg-gray-950" id="icon-features">
    <div class="max-w-[85rem] px-4 sm:px-6 lg:px-8 mx-auto">

        {{-- Section Header --}}
        <div class="text-center mb-16">
            <span class="inline-block px-3 py-1 text-sm font-semibold text-amber-400 bg-amber-900/30 rounded-full mb-4">
                All-in-One Solution
            </span>
            <h2 class="text-3xl lg:text-5xl font-bold text-white mb-4">
            @lang('landing.featureSection2')
        </h2>
            <p class="text-lg text-gray-400 max-w-2xl mx-auto">
                From QR ordering to detailed reports - everything your restaurant needs
            </p>
    </div>

        {{-- Features Grid --}}
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-8">
            
            {{-- Feature 1 --}}
            <div class="group relative bg-gray-800/50 backdrop-blur-sm rounded-2xl p-6 border border-gray-700/50 hover:border-amber-500/50 transition-all duration-300 hover:-translate-y-1">
                <div class="w-14 h-14 bg-gradient-to-br from-amber-500 to-orange-500 rounded-xl flex items-center justify-center mb-5 group-hover:scale-110 transition-transform">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="white" viewBox="0 0 16 16">
                        <path d="M0 .5A.5.5 0 0 1 .5 0h3a.5.5 0 0 1 0 1H1v2.5a.5.5 0 0 1-1 0zm12 0a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-1 0V1h-2.5a.5.5 0 0 1-.5-.5M.5 12a.5.5 0 0 1 .5.5V15h2.5a.5.5 0 0 1 0 1h-3a.5.5 0 0 1-.5-.5v-3a.5.5 0 0 1 .5-.5m15 0a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1 0-1H15v-2.5a.5.5 0 0 1 .5-.5M4 4h1v1H4z"/>
                        <path d="M7 2H2v5h5zM3 3h3v3H3zm2 8H4v1h1z"/>
                        <path d="M7 9H2v5h5zm-4 1h3v3H3zm8-6h1v1h-1z"/>
                        <path d="M9 2h5v5H9zm1 1v3h3V3zM8 8v2h1v1H8v1h2v-2h1v2h1v-1h2v-1h-3V8zm2 2H9V9h1zm4 2h-1v1h-2v1h3zm-4 2v-1H8v1z"/>
                        <path d="M12 9h2V8h-2z"/>
                </svg>
            </div>
                <h3 class="text-lg font-bold text-white mb-2">@lang('landing.iconFeature1')</h3>
                <p class="text-gray-400 text-sm">@lang('landing.iconFeatureDesc1')</p>
            </div>

            {{-- Feature 2 --}}
            <div class="group relative bg-gray-800/50 backdrop-blur-sm rounded-2xl p-6 border border-gray-700/50 hover:border-green-500/50 transition-all duration-300 hover:-translate-y-1">
                <div class="w-14 h-14 bg-gradient-to-br from-green-500 to-emerald-500 rounded-xl flex items-center justify-center mb-5 group-hover:scale-110 transition-transform">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="white" viewBox="0 0 16 16">
                        <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4zm2.5 1a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h2a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-2zm0 3a.5.5 0 0 0 0 1h5a.5.5 0 0 0 0-1h-5zm0 2a.5.5 0 0 0 0 1h1a.5.5 0 0 0 0-1h-1zm3 0a.5.5 0 0 0 0 1h1a.5.5 0 0 0 0-1h-1zm3 0a.5.5 0 0 0 0 1h1a.5.5 0 0 0 0-1h-1zm3 0a.5.5 0 0 0 0 1h1a.5.5 0 0 0 0-1h-1z"/>
                </svg>
            </div>
                <h3 class="text-lg font-bold text-white mb-2">@lang('landing.iconFeature2')</h3>
                <p class="text-gray-400 text-sm">@lang('landing.iconFeatureDesc2')</p>
            </div>

            {{-- Feature 3 --}}
            <div class="group relative bg-gray-800/50 backdrop-blur-sm rounded-2xl p-6 border border-gray-700/50 hover:border-blue-500/50 transition-all duration-300 hover:-translate-y-1">
                <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-xl flex items-center justify-center mb-5 group-hover:scale-110 transition-transform">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="white" viewBox="0 0 16 16">
                        <path d="M15 14s1 0 1-1-1-4-5-4-5 3-5 4 1 1 1 1zm-7.978-1L7 12.996c.001-.264.167-1.03.76-1.72C8.312 10.629 9.282 10 11 10c1.717 0 2.687.63 3.24 1.276.593.69.758 1.457.76 1.72l-.008.002-.014.002zM11 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4m3-2a3 3 0 1 1-6 0 3 3 0 0 1 6 0M6.936 9.28a6 6 0 0 0-1.23-.247A7 7 0 0 0 5 9c-4 0-5 3-5 4q0 1 1 1h4.216A2.24 2.24 0 0 1 5 13c0-1.01.377-2.042 1.09-2.904.243-.294.526-.569.846-.816M4.92 10A5.5 5.5 0 0 0 4 13H1c0-.26.164-1.03.76-1.724.545-.636 1.492-1.256 3.16-1.275ZM1.5 5.5a3 3 0 1 1 6 0 3 3 0 0 1-6 0m3-2a2 2 0 1 0 0 4 2 2 0 0 0 0-4"/>
                </svg>
            </div>
                <h3 class="text-lg font-bold text-white mb-2">@lang('landing.iconFeature3')</h3>
                <p class="text-gray-400 text-sm">@lang('landing.iconFeatureDesc3')</p>
            </div>

            {{-- Feature 4 --}}
            <div class="group relative bg-gray-800/50 backdrop-blur-sm rounded-2xl p-6 border border-gray-700/50 hover:border-purple-500/50 transition-all duration-300 hover:-translate-y-1">
                <div class="w-14 h-14 bg-gradient-to-br from-purple-500 to-pink-500 rounded-xl flex items-center justify-center mb-5 group-hover:scale-110 transition-transform">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="white" viewBox="0 0 16 16">
                        <path d="M14 0H2a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2zM1 3.857C1 3.384 1.448 3 2 3h12c.552 0 1 .384 1 .857v10.286c0 .473-.448.857-1 .857H2c-.552 0-1-.384-1-.857V3.857z"/>
                        <path d="M6.5 7a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm-9 3a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm-9 3a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2z"/>
                </svg>
            </div>
                <h3 class="text-lg font-bold text-white mb-2">@lang('landing.iconFeature4')</h3>
                <p class="text-gray-400 text-sm">@lang('landing.iconFeatureDesc4')</p>
            </div>

            {{-- Feature 5 --}}
            <div class="group relative bg-gray-800/50 backdrop-blur-sm rounded-2xl p-6 border border-gray-700/50 hover:border-rose-500/50 transition-all duration-300 hover:-translate-y-1">
                <div class="w-14 h-14 bg-gradient-to-br from-rose-500 to-red-500 rounded-xl flex items-center justify-center mb-5 group-hover:scale-110 transition-transform">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="white" viewBox="0 0 16 16">
                        <path d="M8.235 1.559a.5.5 0 0 0-.47 0l-7.5 4a.5.5 0 0 0 0 .882L3.188 8 .264 9.559a.5.5 0 0 0 0 .882l7.5 4a.5.5 0 0 0 .47 0l7.5-4a.5.5 0 0 0 0-.882L12.813 8l2.922-1.559a.5.5 0 0 0 0-.882zm3.515 7.008L14.438 10 8 13.433 1.562 10 4.25 8.567l3.515 1.874a.5.5 0 0 0 .47 0zM8 9.433 1.562 6 8 2.567 14.438 6z"/>
                </svg>
            </div>
                <h3 class="text-lg font-bold text-white mb-2">@lang('landing.iconFeature5')</h3>
                <p class="text-gray-400 text-sm">@lang('landing.iconFeatureDesc5')</p>
            </div>

            {{-- Feature 6 --}}
            <div class="group relative bg-gray-800/50 backdrop-blur-sm rounded-2xl p-6 border border-gray-700/50 hover:border-yellow-500/50 transition-all duration-300 hover:-translate-y-1">
                <div class="w-14 h-14 bg-gradient-to-br from-yellow-500 to-amber-500 rounded-xl flex items-center justify-center mb-5 group-hover:scale-110 transition-transform">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="white" viewBox="0 0 16 16">
                        <path d="M3 4.5a.5.5 0 0 1 .5-.5h6a.5.5 0 1 1 0 1h-6a.5.5 0 0 1-.5-.5m0 2a.5.5 0 0 1 .5-.5h6a.5.5 0 1 1 0 1h-6a.5.5 0 0 1-.5-.5m0 2a.5.5 0 0 1 .5-.5h6a.5.5 0 1 1 0 1h-6a.5.5 0 0 1-.5-.5m0 2a.5.5 0 0 1 .5-.5h6a.5.5 0 0 1 0 1h-6a.5.5 0 0 1-.5-.5m0 2a.5.5 0 0 1 .5-.5h6a.5.5 0 0 1 0 1h-6a.5.5 0 0 1-.5-.5M11.5 4a.5.5 0 0 0 0 1h1a.5.5 0 0 0 0-1zm0 2a.5.5 0 0 0 0 1h1a.5.5 0 0 0 0-1zm0 2a.5.5 0 0 0 0 1h1a.5.5 0 0 0 0-1zm0 2a.5.5 0 0 0 0 1h1a.5.5 0 0 0 0-1zm0 2a.5.5 0 0 0 0 1h1a.5.5 0 0 0 0-1z"/>
                        <path d="M2.354.646a.5.5 0 0 0-.801.13l-.5 1A.5.5 0 0 0 1 2v13H.5a.5.5 0 0 0 0 1h15a.5.5 0 0 0 0-1H15V2a.5.5 0 0 0-.053-.224l-.5-1a.5.5 0 0 0-.8-.13L13 1.293l-.646-.647a.5.5 0 0 0-.708 0L11 1.293l-.646-.647a.5.5 0 0 0-.708 0L9 1.293 8.354.646a.5.5 0 0 0-.708 0L7 1.293 6.354.646a.5.5 0 0 0-.708 0L5 1.293 4.354.646a.5.5 0 0 0-.708 0L3 1.293zm-.217 1.198.51.51a.5.5 0 0 0 .707 0L4 1.707l.646.647a.5.5 0 0 0 .708 0L6 1.707l.646.647a.5.5 0 0 0 .708 0L8 1.707l.646.647a.5.5 0 0 0 .708 0L10 1.707l.646.647a.5.5 0 0 0 .708 0L12 1.707l.646.647a.5.5 0 0 0 .708 0l.509-.51.137.274V15H2V2.118z"/>
                </svg>
            </div>
                <h3 class="text-lg font-bold text-white mb-2">@lang('landing.iconFeature6')</h3>
                <p class="text-gray-400 text-sm">@lang('landing.iconFeatureDesc6')</p>
            </div>

            {{-- Feature 7 --}}
            <div class="group relative bg-gray-800/50 backdrop-blur-sm rounded-2xl p-6 border border-gray-700/50 hover:border-teal-500/50 transition-all duration-300 hover:-translate-y-1">
                <div class="w-14 h-14 bg-gradient-to-br from-teal-500 to-cyan-500 rounded-xl flex items-center justify-center mb-5 group-hover:scale-110 transition-transform">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="white" viewBox="0 0 16 16">
                        <path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1"/>
                        <path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1"/>
                </svg>
            </div>
                <h3 class="text-lg font-bold text-white mb-2">@lang('landing.iconFeature7')</h3>
                <p class="text-gray-400 text-sm">@lang('landing.iconFeatureDesc7')</p>
            </div>

            {{-- Feature 8 --}}
            <div class="group relative bg-gray-800/50 backdrop-blur-sm rounded-2xl p-6 border border-gray-700/50 hover:border-indigo-500/50 transition-all duration-300 hover:-translate-y-1">
                <div class="w-14 h-14 bg-gradient-to-br from-indigo-500 to-violet-500 rounded-xl flex items-center justify-center mb-5 group-hover:scale-110 transition-transform">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="white" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M0 0h1v15h15v1H0zm10 3.5a.5.5 0 0 1 .5-.5h4a.5.5 0 0 1 .5.5v4a.5.5 0 0 1-1 0V4.9l-3.613 4.417a.5.5 0 0 1-.74.037L7.06 6.767l-3.656 5.027a.5.5 0 0 1-.808-.588l4-5.5a.5.5 0 0 1 .758-.06l2.609 2.61L13.445 4H10.5a.5.5 0 0 1-.5-.5"/>
                </svg>
            </div>
                <h3 class="text-lg font-bold text-white mb-2">@lang('landing.iconFeature8')</h3>
                <p class="text-gray-400 text-sm">@lang('landing.iconFeatureDesc8')</p>
            </div>
        </div>
    </div>
</section>

{{-- Testimonials Section --}}
<section class="py-20 bg-gradient-to-b from-gray-50 to-white dark:from-gray-800 dark:to-gray-900">
    <div class="max-w-[85rem] px-4 sm:px-6 lg:px-8 mx-auto">

        {{-- Section Header --}}
        <div class="text-center mb-16">
            <span class="inline-block px-3 py-1 text-sm font-semibold text-amber-600 bg-amber-100 rounded-full dark:bg-amber-900/30 dark:text-amber-400 mb-4">
                Testimonials
            </span>
            <h2 class="text-3xl lg:text-5xl font-bold text-gray-900 dark:text-white mb-4">
            @lang('landing.testimonialSection1')
        </h2>
    </div>

        {{-- Testimonials Grid --}}
        <div class="grid md:grid-cols-3 gap-8">
            
            {{-- Testimonial 1 --}}
            <div class="relative bg-white dark:bg-gray-800 rounded-2xl p-8 shadow-lg border border-gray-100 dark:border-gray-700">
                <div class="absolute -top-4 left-8">
                    <div class="w-8 h-8 bg-amber-500 rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z"/>
                        </svg>
                    </div>
                </div>
                <div class="flex mb-4 mt-2">
                    @for($i = 0; $i < 5; $i++)
                    <svg class="w-5 h-5 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                    @endfor
            </div>
                <p class="text-gray-700 dark:text-gray-300 mb-6 italic leading-relaxed">
                    "මේ software එක අපේ restaurant එකේ operations ටික completely change කළා. Orders, tables, staff manage කරන්න ලේසියි!"
                </p>
                <p class="text-gray-600 dark:text-gray-400 mb-6 text-sm">
                    @lang('landing.testimonial1')
                </p>
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-amber-400 to-orange-500 rounded-full flex items-center justify-center text-white font-bold">
                        S
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-900 dark:text-white">Suresh Fernando</h4>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Owner, Colombo Rice & Curry</p>
            </div>
        </div>
            </div>

            {{-- Testimonial 2 --}}
            <div class="relative bg-white dark:bg-gray-800 rounded-2xl p-8 shadow-lg border border-gray-100 dark:border-gray-700">
                <div class="absolute -top-4 left-8">
                    <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z"/>
                        </svg>
                    </div>
                </div>
                <div class="flex mb-4 mt-2">
                    @for($i = 0; $i < 5; $i++)
                    <svg class="w-5 h-5 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                    @endfor
                </div>
                <p class="text-gray-700 dark:text-gray-300 mb-6 italic leading-relaxed">
                    "QR menu එක customers ලට ගොඩක් ලේසියි. Pandemic time එකේ ඉඳන් table turnover ගොඩක් වැඩි වුණා!"
                </p>
                <p class="text-gray-600 dark:text-gray-400 mb-6 text-sm">
                    @lang('landing.testimonial2')
                </p>
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-green-400 to-emerald-500 rounded-full flex items-center justify-center text-white font-bold">
                        K
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-900 dark:text-white">Kumari Perera</h4>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Manager, Galle Fort Café</p>
            </div>
        </div>
            </div>

            {{-- Testimonial 3 --}}
            <div class="relative bg-white dark:bg-gray-800 rounded-2xl p-8 shadow-lg border border-gray-100 dark:border-gray-700">
                <div class="absolute -top-4 left-8">
                    <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z"/>
                        </svg>
                    </div>
                </div>
                <div class="flex mb-4 mt-2">
                    @for($i = 0; $i < 5; $i++)
                    <svg class="w-5 h-5 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                    @endfor
                </div>
                <p class="text-gray-700 dark:text-gray-300 mb-6 italic leading-relaxed">
                    "Kandy branch එකයි Colombo branch එකයි manage කරන්න පුළුවන් එක system එකෙන්. Reports ටික real-time!"
                </p>
                <p class="text-gray-600 dark:text-gray-400 mb-6 text-sm">
                    @lang('landing.testimonial3')
                </p>
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-400 to-indigo-500 rounded-full flex items-center justify-center text-white font-bold">
                        R
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-900 dark:text-white">Ranjan Silva</h4>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Owner, Kottu Express Chain</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Pricing Section --}}
<section class="py-20 bg-white dark:bg-gray-900" id="simple-pricing">
    <div class="max-w-[85rem] px-4 sm:px-6 lg:px-8 mx-auto">
        
        {{-- Section Header --}}
        <div class="text-center mb-16">
            <span class="inline-block px-3 py-1 text-sm font-semibold text-amber-600 bg-amber-100 rounded-full dark:bg-amber-900/30 dark:text-amber-400 mb-4">
                Pricing
            </span>
            <h2 class="text-3xl lg:text-5xl font-bold text-gray-900 dark:text-white mb-4">
                @lang('landing.pricingTitle1')
            </h2>
            <p class="text-lg text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
                @lang('landing.pricingSubTitle1')
            </p>
        </div>

        @include('landing.pricing', ['packages' => $packages, 'modules' => $AllModulesWithFeature])
    </div>
</section>

{{-- FAQ Section --}}
<section class="py-20 bg-gradient-to-b from-gray-50 to-white dark:from-gray-800 dark:to-gray-900" id="user-faqs">
    <div class="max-w-4xl px-4 sm:px-6 lg:px-8 mx-auto">
        
        {{-- Section Header --}}
        <div class="text-center mb-16">
            <span class="inline-block px-3 py-1 text-sm font-semibold text-amber-600 bg-amber-100 rounded-full dark:bg-amber-900/30 dark:text-amber-400 mb-4">
                FAQs
            </span>
            <h2 class="text-3xl lg:text-5xl font-bold text-gray-900 dark:text-white mb-4">
                @lang('landing.faqTitle1')
            </h2>
            <p class="text-lg text-gray-600 dark:text-gray-400">
                @lang('landing.faqSubTitle1')
            </p>
          </div>

        {{-- FAQ Accordion --}}
        <div class="space-y-4">
            {{-- FAQ 1 --}}
            <details class="group bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <summary class="flex items-center justify-between p-6 cursor-pointer list-none">
                    <h3 class="font-semibold text-gray-900 dark:text-white">
                        What payment methods do you support in Sri Lanka?
            </h3>
                    <svg class="w-5 h-5 text-gray-500 group-open:rotate-180 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </summary>
                <div class="px-6 pb-6 text-gray-600 dark:text-gray-400">
                    We support cash payments, card payments (Visa/MasterCard), and can integrate with local payment gateways. Our POS system handles all payment types seamlessly with proper receipt printing in LKR.
          </div>
            </details>

            {{-- FAQ 2 --}}
            <details class="group bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <summary class="flex items-center justify-between p-6 cursor-pointer list-none">
                    <h3 class="font-semibold text-gray-900 dark:text-white">
                        Can I manage multiple branches across Sri Lanka?
            </h3>
                    <svg class="w-5 h-5 text-gray-500 group-open:rotate-180 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </summary>
                <div class="px-6 pb-6 text-gray-600 dark:text-gray-400">
                    Yes! Our multi-branch feature lets you manage restaurants in Colombo, Kandy, Galle, or anywhere else from a single dashboard. Each branch can have its own menu, staff, and reports while you see the consolidated view.
          </div>
            </details>

            {{-- FAQ 3 --}}
            <details class="group bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <summary class="flex items-center justify-between p-6 cursor-pointer list-none">
                    <h3 class="font-semibold text-gray-900 dark:text-white">
                        Does it work with Sri Lankan thermal printers?
                    </h3>
                    <svg class="w-5 h-5 text-gray-500 group-open:rotate-180 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </summary>
                <div class="px-6 pb-6 text-gray-600 dark:text-gray-400">
                    Absolutely! We support all standard 58mm and 80mm thermal printers commonly available in Sri Lanka. KOT tickets and bills print automatically with proper Sinhala/Tamil character support if needed.
                </div>
            </details>

            {{-- FAQ 4 --}}
            <details class="group bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <summary class="flex items-center justify-between p-6 cursor-pointer list-none">
                    <h3 class="font-semibold text-gray-900 dark:text-white">
                        How do I get support if I face issues?
            </h3>
                    <svg class="w-5 h-5 text-gray-500 group-open:rotate-180 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </summary>
                <div class="px-6 pb-6 text-gray-600 dark:text-gray-400">
                    @lang('landing.faqAns1') We also offer WhatsApp support for quick queries and can provide on-site assistance in major Sri Lankan cities.
          </div>
            </details>

            {{-- FAQ 5 --}}
            <details class="group bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <summary class="flex items-center justify-between p-6 cursor-pointer list-none">
                    <h3 class="font-semibold text-gray-900 dark:text-white">
                        Can customers order via WhatsApp?
            </h3>
                    <svg class="w-5 h-5 text-gray-500 group-open:rotate-180 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </summary>
                <div class="px-6 pb-6 text-gray-600 dark:text-gray-400">
                    Yes! Customers can scan your QR code menu and place orders. They'll receive order confirmations and updates via WhatsApp, which is very popular among Sri Lankan customers.
          </div>
            </details>

            {{-- FAQ 6 --}}
            <details class="group bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <summary class="flex items-center justify-between p-6 cursor-pointer list-none">
                    <h3 class="font-semibold text-gray-900 dark:text-white">
                        Is there a free trial available?
            </h3>
                    <svg class="w-5 h-5 text-gray-500 group-open:rotate-180 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </summary>
                <div class="px-6 pb-6 text-gray-600 dark:text-gray-400">
                    @if($trialPackage)
                        Yes! We offer a {{ $trialPackage->trial_days }}-day free trial with full access to all features. No credit card required - just sign up and start using it for your restaurant.
                    @else
                        Yes! We offer a free trial with full access to all features. Contact us to get started with your restaurant.
                    @endif
          </div>
            </details>
        </div>
    </div>
</section>

{{-- Contact Section --}}
<section class="py-20 bg-white dark:bg-gray-900">
    <div class="max-w-6xl px-4 sm:px-6 lg:px-8 mx-auto">
        <div class="grid lg:grid-cols-2 gap-12 items-center">
            
            {{-- Contact Info --}}
            <div>
                <span class="inline-block px-3 py-1 text-sm font-semibold text-amber-600 bg-amber-100 rounded-full dark:bg-amber-900/30 dark:text-amber-400 mb-4">
                    Contact Us
                </span>
                <h2 class="text-3xl lg:text-4xl font-bold text-gray-900 dark:text-white mb-6">
        @lang('landing.contactTitle')
      </h2>
                <p class="text-lg text-gray-600 dark:text-gray-400 mb-8">
                    Ready to transform your restaurant? Get in touch with us for a demo or any questions.
                </p>

                <div class="space-y-6">
                    {{-- Address --}}
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-amber-100 dark:bg-amber-900/30 rounded-xl flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-white mb-1">@lang('landing.addressTitle')</h3>
                            <p class="text-gray-600 dark:text-gray-400">@lang('landing.contactCompany')</p>
                            <p class="text-gray-600 dark:text-gray-400">@lang('landing.contactAddress')</p>
              </div>
            </div>

                    {{-- Email --}}
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-white mb-1">@lang('landing.emailTitle')</h3>
                            <a href="mailto:@lang('landing.contactEmail')" class="text-amber-600 hover:text-amber-700 dark:text-amber-400">
                    @lang('landing.contactEmail')
                    </a>
                        </div>
                    </div>

                    {{-- WhatsApp --}}
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-emerald-100 dark:bg-emerald-900/30 rounded-xl flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6 text-emerald-600" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-white mb-1">WhatsApp Support</h3>
                            <p class="text-gray-600 dark:text-gray-400">Quick responses for urgent queries</p>
                        </div>
                    </div>
                </div>
              </div>

            {{-- Image/Map --}}
            <div class="relative">
                <div class="absolute -inset-4 bg-gradient-to-r from-amber-400 to-orange-400 rounded-2xl blur-2xl opacity-20"></div>
                <div class="relative aspect-w-16 aspect-h-12 rounded-2xl overflow-hidden shadow-2xl">
                    <img src="https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?q=80&w=1000&auto=format&fit=crop" 
                         alt="Restaurant Interior" 
                         class="w-full h-full object-cover rounded-2xl">
                    <div class="absolute inset-0 bg-gradient-to-t from-gray-900/60 to-transparent"></div>
                    <div class="absolute bottom-6 left-6 right-6">
                        <p class="text-white text-lg font-semibold">Serving restaurants across Sri Lanka</p>
                        <p class="text-gray-300 text-sm">Colombo • Kandy • Galle • Jaffna • Negombo</p>
                    </div>
                </div>
          </div>
        </div>
    </div>
</section>

{{-- CTA Section --}}
<section class="py-16 bg-gradient-to-r from-amber-500 via-orange-500 to-red-500">
    <div class="max-w-4xl px-4 sm:px-6 lg:px-8 mx-auto text-center">
        <h2 class="text-3xl lg:text-4xl font-bold text-white mb-4">
            Ready to modernize your restaurant?
        </h2>
        <p class="text-xl text-white/90 mb-8">
            Join hundreds of Sri Lankan restaurants already using our POS system
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('restaurant_signup') }}" 
               class="inline-flex items-center justify-center px-8 py-4 text-lg font-bold text-amber-600 bg-white rounded-xl hover:bg-gray-100 transition-colors shadow-lg">
                @if($trialPackage)
                    Start {{ $trialPackage->trial_days }}-Day Free Trial
                @else
                    Get Started Now
                @endif
            </a>
            <a href="#user-faqs" 
               class="inline-flex items-center justify-center px-8 py-4 text-lg font-semibold text-white border-2 border-white/30 rounded-xl hover:bg-white/10 transition-colors">
                Learn More
            </a>
      </div>
    </div>
</section>

@endsection
