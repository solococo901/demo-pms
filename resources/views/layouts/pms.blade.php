<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'CityHouse PMS')</title>

    @vite([
        'resources/css/app.css',
        'resources/js/app.js'
    ])

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body class="bg-[#f7f8fa] text-[#303641] antialiased">

    <div class="min-h-screen">

        {{-- ========================================================= --}}
        {{-- TOP NAVIGATION --}}
        {{-- ========================================================= --}}
        <header
            class="h-[54px] bg-white border-t-[3px] border-t-[#e7aab8] border-b border-[#e4e7eb] fixed top-0 left-0 right-0 z-50">

            <div class="h-full flex items-center">

                {{-- BRAND --}}
                <div class="w-[170px] shrink-0 px-4 flex items-center gap-2">

                    <div
                        class="w-8 h-8 rounded-lg bg-[#1677ff] text-white flex items-center justify-center font-bold text-sm">
                        C
                    </div>

                    <div class="leading-tight">
                        <div class="text-[14px] font-semibold text-[#25313c]">
                            CityHouse
                        </div>

                        <div class="text-[9px] uppercase tracking-[0.15em] text-[#8c96a0]">
                            PMS
                        </div>
                    </div>

                </div>

                {{-- MAIN NAV --}}
                <nav class="hidden lg:flex h-full items-center">

                    <a href="{{ route('pms.dashboard') }}" class="
                        h-full px-4 flex items-center
                        text-[12px]
                        {{ request()->routeIs('pms.dashboard')
                        ? 'text-[#1677ff] border-b-2 border-[#1677ff] font-medium'
                        : 'text-[#65717d] hover:text-[#1677ff]'
                        }}
                    ">
                        Dashboard
                    </a>

                    <a href="{{ route('pms.inventory.index') }}"
                        class="h-full px-4 flex items-center text-[12px] text-[#65717d] hover:text-[#1677ff]">
                        Inventory
                    </a>

                    <a href="#" class="h-full px-4 flex items-center text-[12px] text-[#65717d] hover:text-[#1677ff]">
                        Bookings
                    </a>

                    <a href="#" class="h-full px-4 flex items-center text-[12px] text-[#65717d] hover:text-[#1677ff]">
                        Rooms & Rates
                    </a>

                    <a href="#" class="h-full px-4 flex items-center text-[12px] text-[#65717d] hover:text-[#1677ff]">
                        Channels
                    </a>

                    <a href="#" class="h-full px-4 flex items-center text-[12px] text-[#65717d] hover:text-[#1677ff]">
                        Property
                    </a>

                    <a href="#" class="h-full px-4 flex items-center text-[12px] text-[#65717d] hover:text-[#1677ff]">
                        Guests
                    </a>

                    <a href="#" class="h-full px-4 flex items-center text-[12px] text-[#65717d] hover:text-[#1677ff]">
                        Reports
                    </a>

                </nav>

                {{-- RIGHT SIDE --}}
                <div class="ml-auto h-full flex items-center gap-3 pr-4">

                    {{-- PROPERTY SELECT --}}
                    <button type="button"
                        class="hidden sm:flex items-center gap-3 h-[34px] px-3 border border-[#dfe3e8] bg-white rounded-[3px] text-[11px] text-[#6b7580] hover:border-[#1677ff]">
                        <span>
                            {{ $property?->name ?? 'Demo Property' }}
                        </span>

                        <svg class="w-3 h-3" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M5.3 7.5 10 12.2l4.7-4.7 1.1 1.1L10 14.4 4.2 8.6z" />
                        </svg>
                    </button>


                    {{-- ADMIN --}}
                    <div class="flex items-center gap-2">

                        <div
                            class="w-8 h-8 rounded-full bg-[#eef3f8] border border-[#dce2e8] flex items-center justify-center text-xs font-semibold text-[#53606d]">
                            A
                        </div>

                        <svg class="w-3 h-3 text-[#8b949e]" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M5.3 7.5 10 12.2l4.7-4.7 1.1 1.1L10 14.4 4.2 8.6z" />
                        </svg>

                    </div>

                </div>

            </div>

        </header>


        {{-- ========================================================= --}}
        {{-- BODY --}}
        {{-- ========================================================= --}}
        <div class="pt-[54px] flex min-h-screen">

            {{-- SIDEBAR --}}
            <aside
                class="hidden md:block fixed top-[54px] bottom-0 left-0 w-[170px] bg-[#f8f9fb] border-r border-[#e4e7eb]">

                <div class="px-4 pt-6 pb-3">

                    <div class="text-[9px] tracking-[0.18em] uppercase font-semibold text-[#a2aab2]">
                        PMS
                    </div>

                </div>


                <nav class="px-2 space-y-[2px]">

                    {{-- DASHBOARD --}}
                    <a href="{{ route('pms.dashboard') }}" class="
                        flex items-center gap-3
                        px-3 py-[9px]
                        rounded-[3px]
                        text-[12px]
                        {{ request()->routeIs('pms.dashboard')
    ? 'bg-[#eaf3ff] text-[#1677ff] border-l-2 border-[#1677ff]'
    : 'text-[#66717d] hover:bg-[#eef1f4]'
                        }}
                    ">

                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                            <rect x="3" y="3" width="7" height="7" rx="1" />
                            <rect x="14" y="3" width="7" height="7" rx="1" />
                            <rect x="3" y="14" width="7" height="7" rx="1" />
                            <rect x="14" y="14" width="7" height="7" rx="1" />
                        </svg>

                        Dashboard

                    </a>


                    {{-- PROPERTY --}}
                    <a href="#"
                        class="flex items-center gap-3 px-3 py-[9px] rounded-[3px] text-[12px] text-[#66717d] hover:bg-[#eef1f4]">

                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                            <path d="M4 21V5a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v16" />
                            <path d="M9 21v-5h6v5" />
                            <path d="M8 7h2M14 7h2M8 11h2M14 11h2" />
                        </svg>

                        Property

                    </a>


                    {{-- ROOM TYPES --}}
                    <a href="{{ route('pms.room-types.index') }}"
                        class="flex items-center gap-3 px-3 py-[9px] rounded-[3px] text-[12px] text-[#66717d] hover:bg-[#eef1f4]">

                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                            <rect x="3" y="5" width="18" height="14" rx="2" />
                            <path d="M3 10h18" />
                        </svg>

                        Room Types

                    </a>


                    {{-- ROOMS --}}
                    <a href="{{ route('pms.rooms.index') }}"
                        class="flex items-center gap-3 px-3 py-[9px] rounded-[3px] text-[12px] text-[#66717d] hover:bg-[#eef1f4] {{ request()->routeIs('pms.rooms.*') ? '...' : '...' }}">

                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                            <path d="M4 20V8l8-4 8 4v12" />
                            <path d="M9 20v-6h6v6" />
                        </svg>

                        Rooms

                    </a>


                    {{-- INVENTORY --}}
                    <a href="#"
                        class="flex items-center gap-3 px-3 py-[9px] rounded-[3px] text-[12px] text-[#66717d] hover:bg-[#eef1f4]">

                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                            <path d="M4 6h16M4 12h16M4 18h16" />
                        </svg>

                        Inventory

                    </a>


                    {{-- BOOKINGS --}}
                    <a href="{{ route('pms.reservations.index') }}"
                        class="flex items-center gap-3 px-3 py-[9px] rounded-[3px] text-[12px] text-[#66717d] hover:bg-[#eef1f4] {{ request()->routeIs('pms.reservations.*') ? '...' : '...' }}">

                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                            <rect x="3" y="5" width="18" height="16" rx="2" />
                            <path d="M8 3v4M16 3v4M3 10h18" />
                        </svg>

                        Reservations

                    </a>


                    {{-- GUESTS --}}
                    <a href="{{ route('pms.guests.index') }}"
                        class="flex items-center gap-3 px-3 py-[9px] rounded-[3px] text-[12px] text-[#66717d] hover:bg-[#eef1f4] {{ request()->routeIs('pms.guests.*') ? '...' : '...' }}">

                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                            <circle cx="12" cy="8" r="4" />
                            <path d="M4 21c0-4 3.6-7 8-7s8 3 8 7" />
                        </svg>

                        Guests

                    </a>


                    {{-- FRONT DESK --}}
                    <a href="{{ route('pms.front-desk.index') }}"
                        class="flex items-center gap-3 px-3 py-[9px] rounded-[3px] text-[12px] text-[#66717d] hover:bg-[#eef1f4] {{ request()->routeIs('pms.front-desk.*') ? '...' : '...' }}">

                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                            <path d="M4 17h16" />
                            <path d="M6 17v3M18 17v3" />
                            <path d="M8 17v-5a4 4 0 0 1 8 0v5" />
                        </svg>

                        Front Desk

                    </a>


                     {{-- HOUSE KEEPING --}}
                    <a href="{{ route('pms.housekeeping.index') }}"
                        class="flex items-center gap-3 px-3 py-[9px] rounded-[3px] text-[12px] text-[#66717d] hover:bg-[#eef1f4] {{ request()->routeIs('pms.housekeeping.*') ? '...' : '...' }}">

                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                            <path d="M4 17h16" />
                            <path d="M6 17v3M18 17v3" />
                            <path d="M8 17v-5a4 4 0 0 1 8 0v5" />
                        </svg>

                        Housekeeping

                    </a>


                    <div class="pt-5 px-3 pb-2">

                        <div class="text-[9px] tracking-[0.18em] uppercase font-semibold text-[#a2aab2]">
                            Distribution
                        </div>

                    </div>


                    {{-- CHANNEX --}}
                    <a href="{{ route('pms.channex.index') }}" class="flex items-center gap-3
                                px-3 py-[9px]
                                rounded-[3px]
                                text-[12px]

                                {{ request()->routeIs('pms.channex.*')
    ? 'bg-[#eaf3ff] text-[#1677ff] border-l-2 border-[#1677ff]'
    : 'text-[#66717d] hover:bg-[#eef1f4]'
                                }}">

                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                            <path d="M8 7h8a4 4 0 0 1 4 4v1" />
                            <path d="m17 9 3 3 3-3" />
                            <path d="M16 17H8a4 4 0 0 1-4-4v-1" />
                            <path d="m7 15-3-3-3 3" />
                        </svg>

                        Channex

                    </a>

                </nav>

            </aside>


            {{-- MAIN CONTENT --}}
            <main class="w-full md:ml-[170px] min-w-0">

                <div class="p-4 md:p-7">

                    @yield('content')

                </div>

            </main>

        </div>

    </div>

</body>

</html>