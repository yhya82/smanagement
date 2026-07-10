<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Log in - {{ $schoolSetting->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 flex items-center justify-center">
    <div class="w-full max-w-sm bg-white p-8 rounded-lg shadow-sm border border-gray-200">
        <div class="flex items-center gap-2 mb-6">
            @if ($schoolSetting->logoUrl())
                <img src="{{ $schoolSetting->logoUrl() }}" class="w-8 h-8 rounded object-cover shrink-0" alt="">
            @endif
            <h1 class="text-lg font-semibold text-gray-900">{{ $schoolSetting->name }}</h1>
        </div>

        @if ($errors->any())
            <div class="mb-4 rounded-md bg-red-50 border border-red-200 p-3 text-sm text-red-700">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                <input id="password" name="password" type="password" required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
            </div>

            <button type="submit"
                class="w-full bg-indigo-600 text-white text-sm font-medium py-2 rounded-md hover:bg-indigo-500">
                Log in
            </button>
        </form>
    </div>
</body>
</html>
