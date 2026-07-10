<div class="max-w-2xl space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">{{ $student->first_name }} {{ $student->last_name }}</h1>
            <p class="text-sm text-gray-500">{{ $student->student_no }} - {{ $student->currentClass?->name ?? 'No class' }}</p>
        </div>
        <span @class([
            'inline-flex px-2 py-0.5 rounded-full text-xs font-medium',
            'bg-green-100 text-green-800' => $student->status->value === 'active',
            'bg-gray-100 text-gray-600' => in_array($student->status->value, ['inactive', 'transferred']),
            'bg-blue-100 text-blue-800' => $student->status->value === 'graduated',
            'bg-red-100 text-red-800' => $student->status->value === 'withdrawn',
        ])>
            {{ ucfirst($student->status->value) }}
        </span>
    </div>

    <section class="bg-white p-6 rounded-lg border border-gray-200">
        <h2 class="text-sm font-medium text-gray-900 mb-3">Profile</h2>
        <dl class="grid grid-cols-2 gap-y-2 text-sm">
            <dt class="text-gray-500">Date of birth</dt>
            <dd>{{ $student->dob->format('M j, Y') }}</dd>
            <dt class="text-gray-500">Gender</dt>
            <dd>{{ ucfirst($student->gender->value) }}</dd>
            <dt class="text-gray-500">Admission date</dt>
            <dd>{{ $student->admission_date->format('M j, Y') }}</dd>
            <dt class="text-gray-500">Email</dt>
            <dd>{{ $student->user->email }}</dd>
        </dl>
    </section>

    <section class="bg-white p-6 rounded-lg border border-gray-200">
        <h2 class="text-sm font-medium text-gray-900 mb-3">Guardians</h2>
        <div class="divide-y divide-gray-100">
            @forelse ($student->guardians as $guardian)
                <div class="py-2 text-sm">
                    <span class="font-medium">{{ $guardian->name }}</span>
                    <span class="text-gray-500">({{ $guardian->relationship }})</span>
                    - {{ $guardian->phone }}
                </div>
            @empty
                <p class="text-sm text-gray-500">No guardians on file.</p>
            @endforelse
        </div>
    </section>

    <section class="bg-white p-6 rounded-lg border border-gray-200 space-y-4">
        <h2 class="text-sm font-medium text-gray-900">Health record</h2>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm text-gray-700">Allergies</label>
                <textarea wire:model="allergies" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm"></textarea>
            </div>
            <div>
                <label class="block text-sm text-gray-700">Conditions</label>
                <textarea wire:model="conditions" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm"></textarea>
            </div>
            <div class="col-span-2">
                <label class="block text-sm text-gray-700">Emergency notes</label>
                <textarea wire:model="emergencyNotes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm"></textarea>
            </div>
        </div>
        <label class="flex items-center gap-2 text-sm text-gray-700">
            <input type="checkbox" wire:model="isConfidential" class="rounded border-gray-300">
            Mark as confidential
        </label>
        <button type="button" wire:click="saveHealthRecord" class="bg-gray-800 text-white text-sm font-medium px-4 py-2 rounded-md hover:bg-gray-700">
            Save Health Record
        </button>
    </section>

    <section class="bg-white p-6 rounded-lg border border-gray-200 space-y-4">
        <h2 class="text-sm font-medium text-gray-900">Transfer class</h2>
        <div class="flex items-end gap-3">
            <div class="flex-1">
                <select wire:model="newClassId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                    <option value="">Select a class...</option>
                    @foreach ($classes as $class)
                        <option value="{{ $class->id }}">{{ $class->name }}</option>
                    @endforeach
                </select>
                @error('newClassId') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <button type="button" wire:click="transfer" wire:confirm="Transfer this student to the selected class?"
                class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded-md hover:bg-indigo-500">
                Transfer
            </button>
        </div>

        <div class="border-t border-gray-100 pt-4">
            <h3 class="text-sm font-medium text-gray-900 mb-2">Change status</h3>
            <div class="flex gap-2">
                @foreach (['active', 'inactive', 'graduated', 'withdrawn'] as $statusOption)
                    @unless ($student->status->value === $statusOption)
                        <button type="button" wire:click="changeStatus('{{ $statusOption }}')"
                            wire:confirm="Mark this student as {{ $statusOption }}?"
                            class="text-xs bg-gray-100 text-gray-700 px-3 py-1.5 rounded-md hover:bg-gray-200">
                            Mark {{ ucfirst($statusOption) }}
                        </button>
                    @endunless
                @endforeach
            </div>
        </div>
    </section>

    <section class="bg-white rounded-lg border border-gray-200">
        <div class="px-4 py-3 border-b border-gray-100">
            <h2 class="text-sm font-medium text-gray-900">Enrollment history</h2>
        </div>
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Class</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Academic year</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Enrolled</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Exited</th>
                    <th class="px-4 py-2 text-left font-medium text-gray-500">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($enrollments as $enrollment)
                    <tr wire:key="enrollment-{{ $enrollment->id }}">
                        <td class="px-4 py-2 font-medium">{{ $enrollment->schoolClass->name }}</td>
                        <td class="px-4 py-2 text-gray-500">{{ $enrollment->academicYear->name }}</td>
                        <td class="px-4 py-2 text-gray-500">{{ $enrollment->enrollment_date->format('M j, Y') }}</td>
                        <td class="px-4 py-2 text-gray-500">{{ $enrollment->exit_date?->format('M j, Y') ?? '-' }}</td>
                        <td class="px-4 py-2 text-gray-500">{{ ucfirst($enrollment->status->value) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-gray-500">No enrollment history.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>
</div>
