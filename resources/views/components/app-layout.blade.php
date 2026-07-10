<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'School Management' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-gray-50 text-gray-900">
    @auth
        <nav class="bg-white border-b border-gray-200">
            <div class="max-w-6xl mx-auto px-4 flex items-center justify-between h-14">
                <div class="flex items-center gap-6">
                    <a href="{{ route('dashboard') }}" class="font-semibold text-gray-900">School Management</a>

                    @if (auth()->user()->hasRole('Registrar'))
                        <a href="{{ route('registrar.dashboard') }}" class="text-sm text-gray-600 hover:text-gray-900">Dashboard</a>
                        <a href="{{ route('registrar.applications.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Applications</a>
                        <a href="{{ route('registrar.applications.create') }}" class="text-sm text-gray-600 hover:text-gray-900">New Application</a>
                    @endif

                    @if (auth()->user()->hasRole('Administrator'))
                        <a href="{{ route('admin.dashboard') }}" class="text-sm text-gray-600 hover:text-gray-900">Dashboard</a>
                        <a href="{{ route('admin.students.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Students</a>
                        <a href="{{ route('admin.students.import') }}" class="text-sm text-gray-600 hover:text-gray-900">Import Students</a>
                        <a href="{{ route('admin.applications.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Application Review</a>
                        <a href="{{ route('admin.grade-review.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Grade Review</a>
                        <a href="{{ route('admin.teachers.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Teachers</a>
                        <a href="{{ route('admin.audit-log.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Audit Log</a>

                        <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                            <button type="button" @click="open = !open" class="text-sm text-gray-600 hover:text-gray-900 flex items-center gap-1">
                                Academic Structure
                                <svg class="size-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                            </button>
                            <div x-show="open" x-cloak class="absolute left-0 mt-2 w-48 bg-white rounded-md shadow-lg border border-gray-200 py-1 z-10">
                                <a href="{{ route('admin.academic-years.index') }}" wire:navigate class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Academic Years</a>
                                <a href="{{ route('admin.terms.index') }}" wire:navigate class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Terms</a>
                                <a href="{{ route('admin.grade-levels.index') }}" wire:navigate class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Grade Levels</a>
                                <a href="{{ route('admin.subjects.index') }}" wire:navigate class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Subjects</a>
                                <a href="{{ route('admin.classes.index') }}" wire:navigate class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Classes</a>
                            </div>
                        </div>
                    @endif

                    @if (auth()->user()->hasRole('Teacher'))
                        <a href="{{ route('teacher.dashboard') }}" class="text-sm text-gray-600 hover:text-gray-900">Dashboard</a>
                    @endif

                    @if (auth()->user()->hasRole('Student'))
                        <a href="{{ route('student.dashboard') }}" class="text-sm text-gray-600 hover:text-gray-900">Dashboard</a>
                        <a href="{{ route('student.results') }}" class="text-sm text-gray-600 hover:text-gray-900">My Results</a>
                        <a href="{{ route('student.attendance') }}" class="text-sm text-gray-600 hover:text-gray-900">My Attendance</a>
                    @endif
                </div>

                <div class="flex items-center gap-4">
                    @php $unreadCount = auth()->user()->notifications()->where('is_read', false)->count(); @endphp
                    <a href="{{ route('notifications') }}" class="relative text-sm text-gray-600 hover:text-gray-900">
                        Notifications
                        @if ($unreadCount > 0)
                            <span class="absolute -top-2 -right-3 inline-flex items-center justify-center w-4 h-4 rounded-full bg-red-500 text-white text-[10px] font-medium">
                                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                            </span>
                        @endif
                    </a>
                    <span class="text-sm text-gray-500">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-sm text-gray-600 hover:text-gray-900">Log out</button>
                    </form>
                </div>
            </div>
        </nav>
    @endauth

    <main class="max-w-6xl mx-auto px-4 py-8">
        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>
