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
                        <a href="{{ route('admin.applications.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Application Review</a>
                    @endif
                </div>

                <div class="flex items-center gap-4">
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
