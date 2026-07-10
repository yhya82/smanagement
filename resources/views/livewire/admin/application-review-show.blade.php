<div class="max-w-2xl space-y-6">
    <h1 class="text-xl font-semibold text-gray-900">
        {{ $application->first_name }} {{ $application->last_name }}
    </h1>

    @if ($decisionError)
        <div class="rounded-md bg-red-50 border border-red-200 p-3 text-sm text-red-700">
            {{ $decisionError }}
        </div>
    @endif

    <section class="bg-white p-6 rounded-lg border border-gray-200">
        <h2 class="text-sm font-medium text-gray-900 mb-3">Applicant details</h2>
        <dl class="grid grid-cols-2 gap-y-2 text-sm">
            <dt class="text-gray-500">Date of birth</dt>
            <dd>{{ $application->dob->format('M j, Y') }}</dd>
            <dt class="text-gray-500">Gender</dt>
            <dd>{{ ucfirst($application->gender->value) }}</dd>
        </dl>
    </section>

    <section class="bg-white p-6 rounded-lg border border-gray-200">
        <h2 class="text-sm font-medium text-gray-900 mb-3">Guardians ({{ $application->guardians->count() }})</h2>
        <div class="divide-y divide-gray-100">
            @foreach ($application->guardians as $guardian)
                <div class="py-2 text-sm">
                    <span class="font-medium">{{ $guardian->name }}</span>
                    <span class="text-gray-500">({{ $guardian->relationship }})</span>
                    - {{ $guardian->phone }}
                </div>
            @endforeach
        </div>
    </section>

    <section class="bg-white p-6 rounded-lg border border-gray-200">
        <h2 class="text-sm font-medium text-gray-900 mb-3">Documents</h2>
        <div class="divide-y divide-gray-100">
            @foreach ($application->documents as $document)
                <div class="py-2 text-sm flex items-center justify-between">
                    <span>{{ $document->documentType->label }}</span>
                    <a href="{{ route('application-documents.stream', $document) }}" target="_blank"
                        class="text-indigo-600 hover:text-indigo-500 text-xs">{{ $document->original_filename }}</a>
                </div>
            @endforeach
        </div>

        @if (count($this->missingDocumentLabels) > 0)
            <p class="text-xs text-amber-600 mt-3">
                Missing required: {{ implode(', ', $this->missingDocumentLabels) }}
            </p>
        @endif
    </section>

    <section class="bg-white p-6 rounded-lg border border-gray-200 space-y-4">
        <h2 class="text-sm font-medium text-gray-900">Decision</h2>

        <div class="flex items-end gap-3">
            <div class="flex-1">
                <label class="block text-sm text-gray-700">Assign to class</label>
                <select wire:model="class_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                    <option value="">Select...</option>
                    @foreach ($classes as $class)
                        <option value="{{ $class->id }}">{{ $class->name }}</option>
                    @endforeach
                </select>
                @error('class_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <button type="button" wire:click="approve" wire:loading.attr="disabled"
                class="bg-green-600 text-white text-sm font-medium px-4 py-2 rounded-md hover:bg-green-500">
                Approve
            </button>
        </div>

        <div class="flex items-end gap-3 border-t border-gray-100 pt-4">
            <div class="flex-1">
                <label class="block text-sm text-gray-700">Rejection reason</label>
                <input type="text" wire:model="rejection_reason" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                @error('rejection_reason') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <button type="button" wire:click="reject" wire:loading.attr="disabled"
                class="bg-red-600 text-white text-sm font-medium px-4 py-2 rounded-md hover:bg-red-500">
                Reject
            </button>
        </div>
    </section>
</div>
