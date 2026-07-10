<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Log in - {{ $schoolSetting->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-white">
    <div class="grid min-h-screen lg:grid-cols-2">

        {{-- Form side --}}
        <div class="flex items-center justify-center px-6 py-12 sm:px-10">
            <div class="w-full max-w-sm">
                <div class="flex items-center gap-2.5 mb-10">
                    @if ($schoolSetting->logoUrl())
                        <img src="{{ $schoolSetting->logoUrl() }}" class="w-10 h-10 rounded-lg object-cover shrink-0" alt="">
                    @else
                        <span class="w-10 h-10 rounded-lg bg-indigo-600 flex items-center justify-center shrink-0">
                            <x-icon name="academic-cap" class="size-5 text-white" />
                        </span>
                    @endif
                    <span class="text-base font-semibold text-gray-900">{{ $schoolSetting->name }}</span>
                </div>

                <h1 class="text-2xl font-semibold text-gray-900">Welcome back</h1>
                <p class="mt-2 text-sm text-gray-500">Sign in to continue to your dashboard.</p>

                @if ($errors->any())
                    <div class="mt-6 rounded-md bg-red-50 border border-red-200 p-3 text-sm text-red-700">
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
                    @csrf

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <div class="relative mt-1.5">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                                <x-icon name="identification" class="size-4" />
                            </span>
                            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                                placeholder="you@school.com"
                                class="block w-full rounded-md border-gray-300 pl-10 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        </div>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                        <div class="relative mt-1.5">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                                <x-icon name="key" class="size-4" />
                            </span>
                            <input id="password" name="password" type="password" required
                                placeholder="••••••••"
                                class="block w-full rounded-md border-gray-300 pl-10 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        </div>
                    </div>

                    <button type="submit"
                        class="w-full bg-indigo-600 text-white text-sm font-medium py-2.5 rounded-md hover:bg-indigo-500 transition-colors">
                        Log in
                    </button>
                </form>

                <p class="mt-8 text-xs text-gray-400">
                    Accounts are created and managed by your administrator. Contact them if you need access or a password reset.
                </p>
            </div>
        </div>

        {{-- Branding side --}}
        <div class="relative hidden lg:flex flex-col justify-between overflow-hidden bg-gradient-to-br from-indigo-600 via-indigo-700 to-purple-800 px-12 py-16 text-white">
            {{-- decorative blurred shapes --}}
            <div class="pointer-events-none absolute -top-24 -right-24 w-96 h-96 rounded-full bg-white/10 blur-3xl"></div>
            <div class="pointer-events-none absolute bottom-0 -left-16 w-80 h-80 rounded-full bg-purple-400/20 blur-3xl"></div>
            <div class="pointer-events-none absolute inset-0" style="background-image: radial-gradient(rgba(255,255,255,0.12) 1px, transparent 1px); background-size: 24px 24px;"></div>

            <div class="relative">
                @if ($schoolSetting->logoUrl())
                    <img src="{{ $schoolSetting->logoUrl() }}" class="w-9 h-9 rounded-lg object-cover shrink-0" alt="">
                @else
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-white/15">
                        <x-icon name="academic-cap" class="size-5" />
                    </span>
                @endif
            </div>

            <div class="relative max-w-md">
                <h2 class="text-3xl font-semibold leading-tight">
                    Everything your school needs, in one place.
                </h2>
                <p class="mt-4 text-indigo-100 text-sm">
                    Admissions, attendance, grading, and communication for every registrar, teacher, and student - all from a single dashboard.
                </p>

                <ul class="mt-8 space-y-4">
                    <li class="flex items-start gap-3">
                        <span class="mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full bg-white/15">
                            <x-icon name="document-text" class="size-3.5" />
                        </span>
                        <span class="text-sm text-indigo-50">Streamlined student admissions and enrollment</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full bg-white/15">
                            <x-icon name="chart-bar" class="size-3.5" />
                        </span>
                        <span class="text-sm text-indigo-50">Real-time attendance, grading, and rankings</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full bg-white/15">
                            <x-icon name="user-group" class="size-3.5" />
                        </span>
                        <span class="text-sm text-indigo-50">Dedicated dashboards for every role</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full bg-white/15">
                            <x-icon name="key" class="size-3.5" />
                        </span>
                        <span class="text-sm text-indigo-50">Secure, permission-based access for every role</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full bg-white/15">
                            <x-icon name="clipboard-list" class="size-3.5" />
                        </span>
                        <span class="text-sm text-indigo-50">Full audit trail for every change made</span>
                    </li>
                </ul>
            </div>

            <div class="relative text-xs text-indigo-200">
                &copy; {{ now()->year }} {{ $schoolSetting->name }}
            </div>
        </div>
    </div>
</body>
</html>
