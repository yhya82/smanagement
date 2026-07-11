<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? \App\Models\SchoolSetting::current()->name }}</title>
    <script>
        // Applies the saved theme app-wide, not just on first load: this
        // re-runs after every wire:navigate transition (Livewire dispatches
        // 'livewire:navigated' on each one), because wire:navigate replaces
        // <body> without a full page reload - an Alpine x-data scope on
        // <html> doesn't reliably survive that swap, but this plain script
        // re-applying the class on the real event does, on every page.
        function applyStoredTheme() {
            const isDark = localStorage.getItem('theme')
                ? localStorage.getItem('theme') === 'dark'
                : window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.classList.toggle('dark', isDark);
        }
        applyStoredTheme();
        document.addEventListener('livewire:navigated', applyStoredTheme);
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100" x-data="{ sidebarOpen: false }">
    @auth
        @php
            $currentUser = auth()->user();
            $unreadCount = $currentUser->notifications()->where('is_read', false)->count();
            $schoolSetting = \App\Models\SchoolSetting::current();
        @endphp

        {{-- Mobile overlay --}}
        <div x-show="sidebarOpen" x-cloak x-on:click="sidebarOpen = false"
            class="fixed inset-0 bg-gray-900/50 z-30 md:hidden"></div>

        {{-- Sidebar --}}
        <aside
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            class="fixed inset-y-0 left-0 z-40 w-64 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 flex flex-col transform transition-transform duration-200 ease-in-out md:translate-x-0">
            <div class="h-14 flex items-center gap-2 px-4 border-b border-gray-200 dark:border-gray-700 shrink-0">
                <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-2 min-w-0">
                    @if ($schoolSetting->logoUrl())
                        <img src="{{ $schoolSetting->logoUrl() }}" class="w-7 h-7 rounded object-cover shrink-0" alt="">
                    @endif
                    <span class="font-semibold text-gray-900 dark:text-gray-100 truncate">{{ $schoolSetting->name }}</span>
                </a>
            </div>

            <nav class="flex-1 overflow-y-auto px-2 py-4 space-y-1">
                @if ($currentUser->hasRole('Registrar'))
                    <x-nav-link :href="route('registrar.dashboard')" :active="request()->routeIs('registrar.dashboard')" icon="home">Dashboard</x-nav-link>
                    <x-nav-link :href="route('registrar.applications.index')" :active="request()->routeIs('registrar.applications.*')" icon="document-text">Applications</x-nav-link>
                    <x-nav-link :href="route('registrar.applications.create')" :active="request()->routeIs('registrar.applications.create')" icon="document-plus">New Application</x-nav-link>
                @endif

                @if ($currentUser->hasRole('Administrator'))
                    <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')" icon="home">Dashboard</x-nav-link>
                    <x-nav-link :href="route('admin.students.index')" :active="request()->routeIs('admin.students.*')" icon="users">Students</x-nav-link>
                    <x-nav-link :href="route('admin.applications.index')" :active="request()->routeIs('admin.applications.*')" icon="document-text">Application Review</x-nav-link>
                    <x-nav-link :href="route('admin.grade-review.index')" :active="request()->routeIs('admin.grade-review.*')" icon="academic-cap">Grade Review</x-nav-link>
                    <x-nav-link :href="route('admin.rankings.index')" :active="request()->routeIs('admin.rankings.*')" icon="arrow-trending-up">Rankings</x-nav-link>
                    <x-nav-link :href="route('admin.promotions.index')" :active="request()->routeIs('admin.promotions.*') || request()->routeIs('admin.promotion-rules.*')" icon="arrow-path">Promotions</x-nav-link>
                    <x-nav-link :href="route('admin.attendance-edit-requests.index')" :active="request()->routeIs('admin.attendance-edit-requests.*')" icon="pencil-square">Attendance Edit Requests</x-nav-link>
                    <x-nav-link :href="route('admin.teachers.index')" :active="request()->routeIs('admin.teachers.*')" icon="user-group">Teachers</x-nav-link>
                    <x-nav-link :href="route('admin.audit-log.index')" :active="request()->routeIs('admin.audit-log.*')" icon="clipboard-list">Audit Log</x-nav-link>
                    <x-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')" icon="identification">Users</x-nav-link>
                    <x-nav-link :href="route('admin.roles.index')" :active="request()->routeIs('admin.roles.*')" icon="key">Roles</x-nav-link>
                    <x-nav-link :href="route('admin.settings.edit')" :active="request()->routeIs('admin.settings.*')" icon="cog">Settings</x-nav-link>

                    @php
                        $academicRoutes = ['admin.academic-years.*', 'admin.terms.*', 'admin.grade-levels.*', 'admin.subjects.*', 'admin.classes.*', 'admin.periods.*'];
                        $academicActive = request()->routeIs($academicRoutes);
                    @endphp
                    <div x-data="{ open: {{ $academicActive ? 'true' : 'false' }} }">
                        <button type="button" x-on:click="open = !open"
                            class="w-full flex items-center justify-between px-3 py-2 rounded-md text-sm font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-gray-100">
                            <span class="flex items-center gap-2.5">
                                <x-icon name="building-library" class="size-5 shrink-0" />
                                <span>Academic Structure</span>
                            </span>
                            <svg :class="open ? 'rotate-180' : ''" class="size-3 shrink-0 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                        </button>
                        <div x-show="open" x-cloak class="mt-1 ml-3 space-y-1 border-l border-gray-100 dark:border-gray-700 pl-3">
                            <x-nav-link :href="route('admin.academic-years.index')" :active="request()->routeIs('admin.academic-years.*')" icon="calendar">Academic Years</x-nav-link>
                            <x-nav-link :href="route('admin.terms.index')" :active="request()->routeIs('admin.terms.*')" icon="clock">Terms</x-nav-link>
                            <x-nav-link :href="route('admin.grade-levels.index')" :active="request()->routeIs('admin.grade-levels.*')" icon="academic-cap">Grade Levels</x-nav-link>
                            <x-nav-link :href="route('admin.subjects.index')" :active="request()->routeIs('admin.subjects.*')" icon="book-open">Subjects</x-nav-link>
                            <x-nav-link :href="route('admin.classes.index')" :active="request()->routeIs('admin.classes.*')" icon="rectangle-group">Classes</x-nav-link>
                            <x-nav-link :href="route('admin.periods.index')" :active="request()->routeIs('admin.periods.*')" icon="table-cells">Periods</x-nav-link>
                        </div>
                    </div>
                @endif

                @if ($currentUser->hasRole('Teacher'))
                    <x-nav-link :href="route('teacher.dashboard')" :active="request()->routeIs('teacher.dashboard')" icon="home">Dashboard</x-nav-link>
                    <x-nav-link :href="route('teacher.timetable')" :active="request()->routeIs('teacher.timetable')" icon="table-cells">My Timetable</x-nav-link>
                @endif

                @if ($currentUser->hasRole('Student'))
                    <x-nav-link :href="route('student.dashboard')" :active="request()->routeIs('student.dashboard')" icon="home">Dashboard</x-nav-link>
                    <x-nav-link :href="route('student.results')" :active="request()->routeIs('student.results')" icon="chart-bar">My Results</x-nav-link>
                    <x-nav-link :href="route('student.attendance')" :active="request()->routeIs('student.attendance')" icon="calendar">My Attendance</x-nav-link>
                    <x-nav-link :href="route('student.timetable')" :active="request()->routeIs('student.timetable')" icon="table-cells">My Timetable</x-nav-link>
                @endif
            </nav>
        </aside>

        {{-- Main column --}}
        <div class="flex flex-col min-h-screen md:pl-64">
            <header class="h-14 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between px-4 sticky top-0 z-20">
                <button type="button" x-on:click="sidebarOpen = true" class="md:hidden text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">
                    <svg class="size-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
                    </svg>
                </button>

                <div class="flex-1"></div>

                <div class="flex items-center gap-3">
                    <button type="button"
                        x-data="{ darkMode: document.documentElement.classList.contains('dark') }"
                        x-on:click="
                            darkMode = ! darkMode;
                            localStorage.setItem('theme', darkMode ? 'dark' : 'light');
                            document.documentElement.classList.toggle('dark', darkMode);
                        "
                        class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 p-1.5 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700"
                        title="Toggle dark mode">
                        <span x-show="!darkMode"><x-icon name="moon" class="size-5" /></span>
                        <span x-show="darkMode" x-cloak><x-icon name="sun" class="size-5" /></span>
                    </button>

                    <a href="{{ route('notifications') }}" wire:navigate
                        class="relative text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 p-1.5 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700">
                        <x-icon name="bell" class="size-5" />
                        @if ($unreadCount > 0)
                            <span class="absolute -top-0.5 -right-0.5 inline-flex items-center justify-center min-w-[16px] h-4 px-1 rounded-full bg-red-500 text-white text-[10px] font-medium">
                                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                            </span>
                        @endif
                    </a>

                    <div class="relative" x-data="{ open: false }" x-on:click.outside="open = false">
                        <button type="button" x-on:click="open = !open" class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100">
                            @if ($currentUser->avatarUrl())
                                <img src="{{ $currentUser->avatarUrl() }}" class="w-7 h-7 rounded-full object-cover" alt="{{ $currentUser->name }}">
                            @else
                                <span class="w-7 h-7 rounded-full bg-indigo-100 dark:bg-indigo-500/20 text-indigo-700 dark:text-indigo-400 flex items-center justify-center text-xs font-medium">
                                    {{ $currentUser->initials() }}
                                </span>
                            @endif
                            <span class="hidden sm:inline max-w-[10rem] truncate">{{ $currentUser->name }}</span>
                            <svg class="size-3 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                        </button>
                        <div x-show="open" x-cloak class="absolute right-0 mt-2 w-52 bg-white dark:bg-gray-800 rounded-md shadow-lg border border-gray-200 dark:border-gray-700 py-1 z-10">
                            <div class="px-4 py-2 border-b border-gray-100 dark:border-gray-700">
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ $currentUser->name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $currentUser->email }}</p>
                            </div>
                            <a href="{{ route('profile') }}" wire:navigate class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">View Profile</a>
                            <a href="{{ route('password.change') }}" wire:navigate class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">Change Password</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">Log out</button>
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
