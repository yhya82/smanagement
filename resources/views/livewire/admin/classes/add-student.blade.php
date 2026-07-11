<div class="max-w-2xl">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Add Student to {{ $class->name }}</h1>
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.classes.import', $class) }}" wire:navigate class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-500">Bulk import instead</a>
            <a href="{{ route('admin.classes.index') }}" wire:navigate class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:text-gray-300 dark:hover:text-gray-200">Back to classes</a>
        </div>
    </div>

    <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
        {{ $class->students()->count() }}{{ $class->capacity ? '/'.$class->capacity.' enrolled' : ' enrolled (no capacity limit)' }}
    </div>

    @if ($enrollError)
        <div class="mb-4 rounded-md bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/30 p-3 text-sm text-red-700 dark:text-red-400">
            {{ $enrollError }}
        </div>
    @endif

    @if ($enrolled)
        <div class="mb-4 rounded-md bg-green-50 dark:bg-green-500/10 border border-green-200 dark:border-green-500/30 p-3 text-sm text-green-700 dark:text-green-400">
            Student enrolled successfully.
        </div>
    @endif

    <form wire:submit="enroll" class="bg-white dark:bg-gray-800 p-6 rounded-lg border border-gray-200 dark:border-gray-700 space-y-4">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm text-gray-700 dark:text-gray-300">First name</label>
                <input type="text" wire:model="first_name" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm text-sm">
                @error('first_name') <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm text-gray-700 dark:text-gray-300">Last name</label>
                <input type="text" wire:model="last_name" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm text-sm">
                @error('last_name') <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm text-gray-700 dark:text-gray-300">Date of birth</label>
                <input type="date" wire:model="dob" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm text-sm">
                @error('dob') <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm text-gray-700 dark:text-gray-300">Gender</label>
                <select wire:model="gender" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm text-sm">
                    <option value="">Select...</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                    <option value="other">Other</option>
                </select>
                @error('gender') <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="border-t border-gray-100 dark:border-gray-700 pt-4 grid grid-cols-3 gap-4">
            <div>
                <label class="block text-sm text-gray-700 dark:text-gray-300">Guardian name</label>
                <input type="text" wire:model="guardian_name" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm text-sm">
                @error('guardian_name') <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm text-gray-700 dark:text-gray-300">Relationship</label>
                <input type="text" wire:model="guardian_relationship" placeholder="Father" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm text-sm">
                @error('guardian_relationship') <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm text-gray-700 dark:text-gray-300">Phone</label>
                <input type="text" wire:model="guardian_phone" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm text-sm">
                @error('guardian_phone') <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <button type="submit" class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded-md hover:bg-indigo-500">
            Enroll student
        </button>
    </form>
</div>
