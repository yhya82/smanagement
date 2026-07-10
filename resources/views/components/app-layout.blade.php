<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'School Management' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-gray-50 text-gray-900" x-data="{ sidebarOpen: false }">
    @auth
        @php
            $currentUser = auth()->user();
            $unreadCount = $currentUser->notifications()->where('is_read', false)->count();
        @endphp

        {{-- Mobile overlay --}}
        <div x-show="sidebarOpen" x-cloak x-on:click="sidebarOpen = false"
            class="fixed inset-0 bg-gray-900/50 z-30 md:hidden"></div>

        {{-- Sidebar --}}
        <aside
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            class="fixed inset-y-0 left-0 z-40 w-64 bg-white border-r border-gray-200 flex flex-col transform transition-transform duration-200 ease-in-out md:translate-x-0 md:static md:shrink-0">
            <div class="h-14 flex items-center px-4 border-b border-gray-200 shrink-0">
                <a href="{{ route('dashboard') }}" wire:navigate class="font-semibold text-gray-900 truncate">School Management</a>
            </div>

            <nav class="flex-1 overflow-y-auto px-2 py-4 space-y-1">
                @if ($currentUser->hasRole('Registrar'))
                    <x-nav-link :href="route('registrar.dashboard')" :active="request()->routeIs('registrar.dashboard')">Dashboard</x-nav-link>
                    <x-nav-link :href="route('registrar.applications.index')" :active="request()->routeIs('registrar.applications.*')">Applications</x-nav-link>
                    <x-nav-link :href="route('registrar.applications.create')" :active="request()->routeIs('registrar.applications.create')">New Application</x-nav-link>
                @endif

                @if ($currentUser->hasRole('Administrator'))
                    <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">Dashboard</x-nav-link>
                    <x-nav-link :href="route('admin.students.index')" :active="request()->routeIs('admin.students.*')">Students</x-nav-link>
                    <x-nav-link :href="route('admin.applications.index')" :active="request()->routeIs('admin.applications.*')">Application Review</x-nav-link>
                    <x-nav-link :href="route('admin.grade-review.index')" :active="request()->routeIs('admin.grade-review.*')">Grade Review</x-nav-link>
                    <x-nav-link :href="route('admin.teachers.index')" :active="request()->routeIs('admin.teachers.*')">Teachers</x-nav-link>
                    <x-nav-link :href="route('admin.audit-log.index')" :active="request()->routeIs('admin.audit-log.*')">Audit Log</x-nav-link>

                    @php
                        $academicRoutes = ['admin.academic-years.*', 'admin.terms.*', 'admin.grade-levels.*', 'admin.subjects.*', 'admin.classes.*'];
                        $academicActive = request()->routeIs($academicRoutes);
                    @endphp
                    <div x-data="{ open: {{ $academicActive ? 'true' : 'false' }} }">
                        <button type="button" x-on:click="open = !open"
                            class="w-full flex items-center justify-between px-3 py-2 rounded-md text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-900">
                            <span>Academic Structure</span>
                            <svg :class="open ? 'rotate-180' : ''" class="size-3 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                        </button>
                        <div x-show="open" x-cloak class="mt-1 ml-3 space-y-1 border-l border-gray-100 pl-3">
                            <x-nav-link :href="route('admin.academic-years.index')" :active="request()->routeIs('admin.academic-years.*')">Academic Years</x-nav-link>
                            <x-nav-link :href="route('admin.terms.index')" :active="request()->routeIs('admin.terms.*')">Terms</x-nav-link>
                            <x-nav-link :href="route('admin.grade-levels.index')" :active="request()->routeIs('admin.grade-levels.*')">Grade Levels</x-nav-link>
                            <x-nav-link :href="route('admin.subjects.index')" :active="request()->routeIs('admin.subjects.*')">Subjects</x-nav-link>
                            <x-nav-link :href="route('admin.classes.index')" :active="request()->routeIs('admin.classes.*')">Classes</x-nav-link>
                        </div>
                    </div>
                @endif

                @if ($currentUser->hasRole('Teacher'))
                    <x-nav-link :href="route('teacher.dashboard')" :active="request()->routeIs('teacher.dashboard')">Dashboard</x-nav-link>
                @endif

                @if ($currentUser->hasRole('Student'))
                    <x-nav-link :href="route('student.dashboard')" :active="request()->routeIs('student.dashboard')">Dashboard</x-nav-link>
                    <x-nav-link :href="route('student.results')" :active="request()->routeIs('student.results')">My Results</x-nav-link>
                    <x-nav-link :href="route('student.attendance')" :active="request()->routeIs('student.attendance')">My Attendance</x-nav-link>
                @endif
            </nav>
        </aside>

        {{-- Main column --}}
        <div class="flex flex-col min-h-screen md:pl-64">
            <header class="h-14 bg-white border-b border-gray-200 flex items-center justify-between px-4 sticky top-0 z-20">
                <button type="button" x-on:click="sidebarOpen = true" class="md:hidden text-gray-500 hover:text-gray-700">
                    <svg class="size-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
                    </svg>
                </button>

                <div class="flex-1"></div>

                <div class="flex items-center gap-3">
                    <a href="{{ route('notifications') }}" wire:navigate
                        class="relative text-gray-500 hover:text-gray-700 p-1.5 rounded-full hover:bg-gray-100">
                        <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                        </svg>
                        @if ($unreadCount > 0)
                            <span class="absolute -top-0.5 -right-0.5 inline-flex items-center justify-center min-w-[16px] h-4 px-1 rounded-full bg-red-500 text-white text-[10px] font-medium">
                                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                            </span>
                        @endif
                    </a>

                    <div class="relative" x-data="{ open: false }" x-on:click.outside="open = false">
                        <button type="button" x-on:click="open = !open" class="flex items-center gap-2 text-sm text-gray-700 hover:text-gray-900">
                            @if ($currentUser->avatarUrl())
                                <img src="{{ $currentUser->avatarUrl() }}" class="w-7 h-7 rounded-full object-cover" alt="{{ $currentUser->name }}">
                            @else
                                <span class="w-7 h-7 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-medium">
                                    {{ $currentUser->initials() }}
                                </span>
                            @endif
                            <span class="hidden sm:inline max-w-[10rem] truncate">{{ $currentUser->name }}</span>
                            <svg class="size-3 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                        </button>
                        <div x-show="open" x-cloak class="absolute right-0 mt-2 w-52 bg-white rounded-md shadow-lg border border-gray-200 py-1 z-10">
                            <div class="px-4 py-2 border-b border-gray-100">
                                <p class="text-sm font-medium text-gray-900 truncate">{{ $currentUser->name }}</p>
                                <p class="text-xs text-gray-500 truncate">{{ $currentUser->email }}</p>
                            </div>
                            <a href="{{ route('profile') }}" wire:navigate class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">View Profile</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Log out</button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <main class="flex-1 max-w-6xl mx-auto px-4 py-8 w-full">
                {{ $slot }}
            </main>
        </div>
    @else
        {{ $slot }}
    @endauth

    @livewireScripts
</body>
</html>
