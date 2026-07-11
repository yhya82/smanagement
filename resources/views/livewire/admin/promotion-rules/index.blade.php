<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Promotion Rules</h1>
        <button type="button" wire:click="$toggle('showCreateForm')"
            class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded-md hover:bg-indigo-500">
            {{ $showCreateForm ? 'Cancel' : 'New Rule' }}
        </button>
    </div>

    @if ($showCreateForm)
        <form wire:submit="create" class="bg-white dark:bg-gray-800 p-6 rounded-lg border border-gray-200 dark:border-gray-700 mb-6 space-y-4">
            <div class="grid grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm text-gray-700 dark:text-gray-300">Grade level</label>
                    <select wire:model="grade_level_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm text-sm">
                        <option value="">Select...</option>
                        @foreach ($gradeLevels as $gradeLevel)
                            <option value="{{ $gradeLevel->id }}">{{ $gradeLevel->name }}</option>
                        @endforeach
                    </select>
                    @error('grade_level_id') <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm text-gray-700 dark:text-gray-300">Criteria</label>
                    <select wire:model="criteria_type" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm text-sm">
                        <option value="">Select...</option>
                        @foreach ($criteriaTypes as $type)
                            <option value="{{ $type->value }}">{{ $type->value === 'rank_threshold' ? 'Rank at or above' : 'Average at or above' }}</option>
                        @endforeach
                    </select>
                    @error('criteria_type') <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm text-gray-700 dark:text-gray-300">Threshold</label>
                    <input type="number" step="0.01" wire:model="threshold_value" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm text-sm">
                    @error('threshold_value') <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm text-gray-700 dark:text-gray-300">Promote to class</label>
                    <select wire:model="target_class_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm text-sm">
                        <option value="">Select...</option>
                        @foreach ($classes as $class)
                            <option value="{{ $class->id }}">{{ $class->name }}</option>
                        @endforeach
                    </select>
                    @error('target_class_id') <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400">
                "Rank at or above" promotes students whose term ranking position is at or better than the threshold (e.g. top 10).
                "Average at or above" promotes students whose term average is at or above the threshold percentage.
            </p>
            <button type="submit" class="bg-gray-800 text-white text-sm font-medium px-4 py-2 rounded-md hover:bg-gray-700 dark:bg-gray-600 dark:hover:bg-gray-500">
                Create
            </button>
        </form>
    @endif

    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Grade level</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Criteria</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Threshold</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Promotes to</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($rules as $rule)
                    <tr wire:key="rule-{{ $rule->id }}">
                        <td class="px-4 py-2 font-medium">{{ $rule->gradeLevel->name }}</td>
                        <td class="px-4 py-2 text-gray-500 dark:text-gray-400">{{ $rule->criteria_type->value === 'rank_threshold' ? 'Rank at or above' : 'Average at or above' }}</td>
                        <td class="px-4 py-2 text-gray-500 dark:text-gray-400">{{ $rule->threshold_value }}</td>
                        <td class="px-4 py-2 text-gray-500 dark:text-gray-400">{{ $rule->targetClass->name ?? '-' }}</td>
                        <td class="px-4 py-2">
                            <span @class([
                                'inline-flex px-2 py-0.5 rounded-full text-xs font-medium',
                                'bg-green-100 dark:bg-green-500/10 text-green-800 dark:text-green-400' => $rule->is_active,
                                'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400' => ! $rule->is_active,
                            ])>
                                {{ $rule->is_active ? 'Active' : 'Disabled' }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-right whitespace-nowrap">
                            <button type="button" wire:click="toggleActive({{ $rule->id }})"
                                class="text-xs {{ $rule->is_active ? 'text-red-600 dark:text-red-400 hover:text-red-500' : 'text-green-600 hover:text-green-500' }}">
                                {{ $rule->is_active ? 'Disable' : 'Enable' }}
                            </button>
                            <span class="text-gray-300 dark:text-gray-600 mx-1">|</span>
                            <button type="button" wire:click="delete({{ $rule->id }})" wire:confirm="Delete this rule?"
                                class="text-xs text-red-600 dark:text-red-400 hover:text-red-500">
                                Delete
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">No promotion rules configured yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
