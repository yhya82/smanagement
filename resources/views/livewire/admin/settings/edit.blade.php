<div class="max-w-lg">
    <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">School Settings</h1>

    <div class="flex gap-4 border-b border-gray-200 dark:border-gray-700 mb-6 text-sm">
        <a href="{{ route('admin.settings.edit') }}" wire:navigate
            class="px-1 pb-2 border-b-2 border-indigo-600 text-indigo-600 dark:text-indigo-400 font-medium">General</a>
        <a href="{{ route('admin.settings.calendar') }}" wire:navigate
            class="px-1 pb-2 text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">School Calendar</a>
    </div>

    @if ($saved)
        <div class="mb-6 rounded-md bg-green-50 dark:bg-green-500/10 border border-green-200 dark:border-green-500/30 p-3 text-sm text-green-700 dark:text-green-400">
            Settings saved.
        </div>
    @endif

    <form wire:submit="save" class="bg-white dark:bg-gray-800 p-6 rounded-lg border border-gray-200 dark:border-gray-700 space-y-4">
        <div class="flex items-center gap-4">
            @if ($logo)
                <img src="{{ $logo->temporaryUrl() }}" class="w-16 h-16 rounded-md object-cover border border-gray-200 dark:border-gray-700" alt="">
            @elseif ($setting->logoUrl())
                <img src="{{ $setting->logoUrl() }}" class="w-16 h-16 rounded-md object-cover border border-gray-200 dark:border-gray-700" alt="">
            @else
                <span class="w-16 h-16 rounded-md bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-xs text-gray-400">No logo</span>
            @endif
            <div>
                <label class="block text-sm text-gray-700 dark:text-gray-300">Logo (optional)</label>
                <input type="file" wire:model="logo" accept="image/*" class="mt-1 text-sm">
                @error('logo') <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label class="block text-sm text-gray-700 dark:text-gray-300">School name</label>
            <input type="text" wire:model="name" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm text-sm">
            @error('name') <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm text-gray-700 dark:text-gray-300">Address</label>
                <input type="text" wire:model="address" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm text-sm">
                @error('address') <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm text-gray-700 dark:text-gray-300">City / location</label>
                <input type="text" wire:model="city" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm text-sm">
                @error('city') <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm text-gray-700 dark:text-gray-300">Phone</label>
                <input type="text" wire:model="phone" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm text-sm">
                @error('phone') <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm text-gray-700 dark:text-gray-300">Email</label>
                <input type="email" wire:model="email" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm text-sm">
                @error('email') <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label class="block text-sm text-gray-700 dark:text-gray-300">Website</label>
            <input type="text" wire:model="website" placeholder="https://" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm text-sm">
            @error('website') <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
            <h2 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-1">Grade weighting</h2>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">How much each exam counts toward a subject's combined term score. Must add up to 100.</p>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-gray-700 dark:text-gray-300">Midterm weight (%)</label>
                    <input type="number" wire:model="midterm_weight" min="0" max="100" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm text-sm">
                    @error('midterm_weight') <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm text-gray-700 dark:text-gray-300">Final weight (%)</label>
                    <input type="number" wire:model="final_weight" min="0" max="100" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm text-sm">
                    @error('final_weight') <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <button type="submit" class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded-md hover:bg-indigo-500">
            Save
        </button>
    </form>
</div>
