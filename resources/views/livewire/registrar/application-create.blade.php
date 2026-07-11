<div class="max-w-2xl">
    <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-6">New Student Application</h1>

    <form wire:submit="save" class="space-y-8">
        <section class="bg-white dark:bg-gray-800 p-6 rounded-lg border border-gray-200 dark:border-gray-700 space-y-4">
            <h2 class="text-sm font-medium text-gray-900 dark:text-gray-100">Applicant</h2>

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
        </section>

        <section class="bg-white dark:bg-gray-800 p-6 rounded-lg border border-gray-200 dark:border-gray-700 space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-medium text-gray-900 dark:text-gray-100">Guardians</h2>
                <button type="button" wire:click="addGuardian" class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-500">
                    + Add guardian
                </button>
            </div>
            @error('guardians') <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror

            @foreach ($guardians as $index => $guardian)
                <div class="grid grid-cols-2 gap-4 border-t border-gray-100 dark:border-gray-700 pt-4 first:border-t-0 first:pt-0" wire:key="guardian-{{ $index }}">
                    <div>
                        <label class="block text-sm text-gray-700 dark:text-gray-300">Name</label>
                        <input type="text" wire:model="guardians.{{ $index }}.name" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm text-sm">
                        @error("guardians.$index.name") <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700 dark:text-gray-300">Relationship</label>
                        <input type="text" wire:model="guardians.{{ $index }}.relationship" placeholder="Mother, Father, ..." class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm text-sm">
                        @error("guardians.$index.relationship") <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700 dark:text-gray-300">Phone</label>
                        <input type="text" wire:model="guardians.{{ $index }}.phone" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm text-sm">
                        @error("guardians.$index.phone") <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700 dark:text-gray-300">Email (optional)</label>
                        <input type="email" wire:model="guardians.{{ $index }}.email" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm text-sm">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm text-gray-700 dark:text-gray-300">Address (optional)</label>
                        <input type="text" wire:model="guardians.{{ $index }}.address" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm text-sm">
                    </div>
                    @if (count($guardians) > 1)
                        <div class="col-span-2 text-right">
                            <button type="button" wire:click="removeGuardian({{ $index }})" class="text-xs text-red-600 dark:text-red-400 hover:text-red-500">
                                Remove this guardian
                            </button>
                        </div>
                    @endif
                </div>
            @endforeach
        </section>

        <button type="submit" class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded-md hover:bg-indigo-500">
            Create application
        </button>
    </form>
</div>
