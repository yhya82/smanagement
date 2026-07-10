<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold text-gray-900">Teachers</h1>
        <button type="button" wire:click="$toggle('showCreateForm')"
            class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded-md hover:bg-indigo-500">
            {{ $showCreateForm ? 'Cancel' : 'Onboard Teacher' }}
        </button>
    </div>

    @if ($showCreateForm)
        <form wire:submit="create" class="bg-white p-6 rounded-lg border border-gray-200 mb-6 space-y-4">
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm text-gray-700">Name</label>
                    <input type="text" wire:model="name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                    @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm text-gray-700">Email</label>
                    <input type="email" wire:model="email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                    @error('email') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm text-gray-700">Hire date</label>
                    <input type="date" wire:model="hire_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                    @error('hire_date') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
            <p class="text-xs text-gray-500">An employee number is generated automatically, and a temporary password is set - the teacher will be asked to change it on first login.</p>
            <button type="submit" class="bg-gray-800 text-white text-sm font-medium px-4 py-2 rounded-md hover:bg-gray-700">
                Onboard
            </button>
        </form>
    @endif

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Name</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Employee no.</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Status</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Assignments</th>
                    <th></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($teachers as $teacher)
                    <tr wire:key="teacher-{{ $teacher->id }}">
                        <td class="px-4 py-2 font-medium">{{ $teacher->user->name }}</td>
                        <td class="px-4 py-2 text-gray-500">{{ $teacher->employee_no }}</td>
                        <td class="px-4 py-2">
                            <span @class([
                                'inline-flex px-2 py-0.5 rounded-full text-xs font-medium',
                                'bg-yellow-100 text-yellow-800' => $teacher->status->value === 'pending',
                                'bg-green-100 text-green-800' => $teacher->status->value === 'active',
                                'bg-gray-100 text-gray-600' => $teacher->status->value === 'inactive',
                            ])>
                                {{ ucfirst($teacher->status->value) }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-gray-500">{{ $teacher->subject_assignments_count }}</td>
                        <td class="px-4 py-2 text-right">
                            <a href="{{ route('admin.teachers.show', $teacher) }}" wire:navigate class="text-xs text-indigo-600 hover:text-indigo-500">Manage</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-gray-500">No teachers onboarded yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
